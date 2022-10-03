<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\ApiService;
use App\Services\FormGroupService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;
use function Ramsey\Uuid\v4;

class YookassaPaymentGateway implements PaymentGatewayInterface
{
    private $transactionService;
    private $formGroupService;
    private $gatewayService;
    private $paymentService;
    private $apiService;

    public function __construct(
        TransactionService $transactionService,
        FormGroupService $formGroupService,
        GatewayService $gatewayService,
        PaymentService $paymentService,
        ApiService $apiService
    )
    {
        $this->transactionService = $transactionService;
        $this->formGroupService   = $formGroupService;
        $this->gatewayService     = $gatewayService;
        $this->paymentService     = $paymentService;
        $this->apiService         = $apiService;
    }

    public function isSandBox(): bool
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    public function getPaymentGatewayName()
    {
        return 'yookassa';
    }

    public function checkout()
    {
        return true;
    }

    public function redirto($params)
    {
        $t_id = $params['t_id'];
        $pa_id = $params['pa_id'] ?? null;
        $transaction = $this->transactionService->getTransaction($t_id);
        if (blank($transaction)) {
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: transaction not found'
            ];
        } else if ($pa_id) {
            $this->transactionService->updateById($t_id, ['t_xref_pa_id' => $pa_id]);
        }
        $yookassa_config = $this->getYookassaConfig($transaction['t_client'], $transaction['t_issuer'], $pa_id);

        $yookassa_params = [
            'amount' => [
                'value' => $transaction['t_amount'],
                'currency' => $transaction['t_currency'],
            ],
            'payment_method_data' => [
                'type' => 'bank_card',
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url'=> get_callback_url($yookassa_config['return_url']) . '?t_id=' . $transaction['t_id'],
            ],
            'description' => $transaction['t_transaction_id'],
        ];

        $payment = $this->apiService->yookassaCreatePayment($yookassa_params, $yookassa_config, $transaction['t_transaction_id']);
        // If the first time to generate orders, will return the orderId, otherwise return an error message, go to the database to find the orderId
        if ($payment['status'] == 200) {
            $this->transactionService->updateById($transaction['t_id'], ['t_gateway_transaction_id' => $payment['body']['confirmation']['confirmation_url']]);
            $confirmation_url = $payment['body']['confirmation']['confirmation_url'];
            $orderId = $payment['body']['id'];
        } elseif ($payment['status'] == 400 && $payment['body']['code'] === 'invalid_request' && !empty($transaction['t_gateway_transaction_id'])) {
            $query = parse_url($transaction['t_gateway_transaction_id'])['query'];
            $confirmation_url = $transaction['t_gateway_transaction_id'];
            $orderId = convertUrlQuery($query)['orderId'];
        } else {
            return [
                'status'  => 'error',
                'message' => $payment['body']['description']
            ];
        }
        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $transaction);

        return [
            'form_method' => 'get',
            'form_action' => $confirmation_url,
            'form_fields' => ['orderId' => $orderId],
        ];
    }

    public function notify($params)
    {
        return true;
    }

    public function return($params)
    {
        $transaction_id = $params['t_id'] ?? '';
        $transaction = $this->transactionService->getTransaction($transaction_id);
        if (empty($transaction)) {
            Log::warning("ONLINE PAYMENT, Yookassa : No transaction found in the database for " . $transaction_id . "\n" .
                json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return [
                'status'  => 'error',
                'message' => 'Transaction ERROR: transaction not found'
            ];
        }

        $query = parse_url($transaction['t_gateway_transaction_id'])['query'];
        $orderId = convertUrlQuery($query)['orderId'];

        $yookassa_config = $this->getYookassaConfig($transaction['t_client'], $transaction['t_issuer'], $transaction['t_xref_pa_id']);

        $paymentInfo = $this->apiService->getYookassaPayment($orderId, $yookassa_config, v4());

        if ($paymentInfo['body']['status'] === 'waiting_for_capture') {
            $paymentCaptureInfo = $this->apiService->yookassaCapturePayment($orderId, $yookassa_config, v4());
        } else {
            return [
                'is_success' => 'failure',
                'description' => $transaction['t_transaction_id'],
                'issuer' => $transaction['t_issuer'],
                'amount' => $transaction['t_amount'],
                'message' => 'failure',
                'href' => $transaction['t_redirect_url']
            ];
        }

        $isValid = $paymentCaptureInfo['body']['status'] === 'succeeded';
        if (!$isValid) {
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'fail');
            return [
                'status'  => 'error',
                'message' => 'Request ERROR: params validate failed'
            ];
        }
        $this->paymentService->saveTransactionLog($transaction_id, $paymentCaptureInfo, $this->getPaymentGatewayName());

        $confirm_params = [
            'gateway'                => $this->getPaymentGatewayName(),
            'amount'                 => $paymentCaptureInfo['body']['amount']['value'],
            'currency'               => $paymentCaptureInfo['body']['amount']['currency'],
            'transaction_id'         => $paymentCaptureInfo['body']['description'],
            'gateway_transaction_id' => $transaction['t_gateway_transaction_id'],
        ];
        $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'success');
        return $this->paymentService->confirm($transaction, $confirm_params);
    }

    public function getYookassaConfig($client, $issuer, $pa_id): array
    {
        $config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $pa_id);
        $yookassa_config = array_merge($config['common'], $this->getPaySecret($config));
        return $yookassa_config;
    }

    private function getPaySecret($pay_config) {
        if ($this->gatewayService->getClientUseFile()) {
            $app_env = $this->isSandBox();
            $is_live = ($pay_config['common']['env'] == 'live');
            $key = ($is_live && !$app_env) ? 'prod' : 'sandbox';
        } else {
            $key = 'config';
        }
        return $pay_config[$key];
    }
}
