<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\ApiService;
use App\Services\FormGroupService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;
use YooKassa\Client;

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

    public function redirto($t_id)
    {
        $transaction = $this->transactionService->getTransaction($t_id);
        if (empty($transaction)) {
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: transaction not found'
            ];
        }
        $client      = $transaction['t_client'];
        $issuer      = $transaction['t_issuer'];
        $config      = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName());
        $yookassa_config = array_merge($config['common'], $this->isSandbox() ? $config['sandbox'] : $config['prod']);

        $yookassa = new Client();
        $yookassa->setAuth($yookassa_config['shop_id'], $yookassa_config['secret_key']);
        $payment  = $yookassa->createPayment([
            'amount' => [
                'value' => $transaction['t_amount'],
                'currency' => $transaction['t_currency'],
            ],
            'payment_method_data' => [
                'type' => 'bank_card',
            ],
            'confirmation' => [
                'type' => "redirect",
                'return_url'=> get_callback_url($yookassa_config['return_url']),
            ],
            'description' => $transaction['t_transaction_id'],
        ], $transaction['t_transaction_id']);

        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $transaction);

        return [
            'form_method' => 'get',
            'form_action' => $payment['confirmation']['confirmation_url'],
            'form_fields' => ['orderId' => $payment['id']],
        ];
    }

    public function notify($params)
    {
        return true;
    }

    public function return($params)
    {
        info($params);
        $transaction_id = $params['id'] ?? '';
        $this->paymentService->saveTransactionLog($transaction_id, $params, $this->getPaymentGatewayName());

        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $transaction_id]);
        if (empty($transaction)) {
            Log::warning("ONLINE PAYMENT, Yookassa : No transaction found in the database for " . $transaction_id . "\n" .
                json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return [
                'status'  => 'error',
                'message' => 'Transaction ERROR: transaction not found'
            ];
        }
        if (isset($params['status']) && $params['status'] == 'waiting_for_capture') {
            return [
                'is_success' => 'waiting_for_capture',
                'description' => $transaction['t_transaction_id'],
                'issuer' => $transaction['t_issuer'],
                'amount' => $transaction['t_amount'],
                'message' => $params['status'],
                'href' => $transaction['t_redirect_url']
            ];
        }
        $isValid = $params['status'] === 'succeeded';
        if (!$isValid) {
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'fail');
            return [
                'status'  => 'error',
                'message' => 'Request ERROR: params validate failed'
            ];
        }

        $confirm_params = [
            'gateway'                => $this->getPaymentGatewayName(),
            'amount'                 => $params['amount']['value'],
            'currency'               => $params['amount']['currency'],
            'transaction_id'         => $params['description'],
            'gateway_transaction_id' => $params['description'],
        ];
        $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'success');
        return $this->paymentService->confirm($transaction, $confirm_params);
    }
}
