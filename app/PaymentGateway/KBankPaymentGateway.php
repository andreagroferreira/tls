<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\ApiService;
use App\Services\GatewayService;
use App\Services\PaymentInitiateService;
use App\Services\PaymentService;
use App\Services\TransactionItemsService;
use App\Services\TransactionLogsService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;

class KBankPaymentGateway implements PaymentGatewayInterface
{
    private $transactionLogsService;
    private $transactionService;
    private $transactionItemsService;
    private $paymentInitiateService;
    private $gatewayService;
    private $paymentService;
    private $apiService;

    public function __construct(
        TransactionService $transactionService,
        TransactionLogsService $transactionLogsService,
        TransactionItemsService $transactionItemsService,
        PaymentInitiateService $paymentInitiateService,
        GatewayService $gatewayService,
        PaymentService $paymentService,
        ApiService $apiService
    ) {
        $this->transactionService = $transactionService;
        $this->transactionLogsService = $transactionLogsService;
        $this->transactionItemsService = $transactionItemsService;
        $this->paymentInitiateService = $paymentInitiateService;
        $this->gatewayService = $gatewayService;
        $this->paymentService = $paymentService;
        $this->apiService = $apiService;
    }

    public function getPaymentGatewayName()
    {
        return 'k-bank';
    }

    public function isSandBox()
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    public function checkout()
    {
    }

    public function notify($return_params)
    {
        $charge_id = $return_params['id'];
        $checksum = $return_params['checksum'];
        $status = $return_params['status'];
        $transaction_state = $return_params['transaction_state'];
        $transaction = $this->transactionService->fetchTransaction(['t_gateway_transaction_id' => $charge_id, 't_tech_deleted' => false]);
        $kbank_config = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName(), $transaction['t_xref_pa_id']);
        $is_live = $kbank_config['common']['env'] == 'live' ? true : false;
        $app_env = $this->isSandBox();
        if (!$this->gatewayService->getClientUseFile()) {
            $host = $kbank_config['config']['host'] ?? '';
            $secret = $kbank_config['config']['secret'] ?? '';
        } elseif ($is_live && !$app_env) {
            $host = $kbank_config['production']['host'];
            $secret = $kbank_config['production']['secret'];
        } else {
            $host = $kbank_config['sandbox']['sandbox_host'];
            $secret = $kbank_config['sandbox']['sandbox_secret'];
        }
        $header = ['x-api-key: ' . $secret, 'Content-Type: application/json'];
        $charge_host = $host . '/card/v2/charge/' . $charge_id;
        $charges_payments = $this->paymentInitiateService->paymentInitiate('get', $charge_host, '', false, $header);
        if (strpos($charges_payments, 'error') !== false) {
            return ['status' => 'fail', 'content' => $charges_payments];
        }
        $charges_payments_data = json_decode($charges_payments, true);
        $hash_string = $charge_id . str_replace(',', '', number_format($transaction['t_amount'], 4)) . $transaction['t_currency'] . $charges_payments_data['status'] . $charges_payments_data['transaction_state'] . $secret;
        $hash = hash('SHA256', $hash_string);

        // 验证数字签名
        if ($checksum != $hash) {
            Log::warning('ONLINE PAYMENT, K-BANK: digital signature check failed : ' . json_encode($_POST, JSON_UNESCAPED_UNICODE));

            return 'APPROVED';
        }

        // 交易成功 修改数据库
        $confirm_params = [
            'gateway' => $this->getPaymentGatewayName(),
            'amount' => $return_params['amount'],
            'currency' => $return_params['currency'],
            'transaction_id' => $transaction['t_transaction_id'],
            'gateway_transaction_id' => $charge_id,
        ];
        $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(), $transaction, $return_params, 'success');
        $response = $this->paymentService->confirm($transaction, $confirm_params);
        if ($response['is_success'] != 'ok') {
            exit;
        }
        // 核对支付授权状态
        if ($status == 'success' && $transaction_state == 'Authorized') {
            return 'OK';
        }
        $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(), $transaction, $return_params, 'fail');

        return 'ONLINE PAYMENT, K-BANK: Payment authorization check failed : ' . json_encode($return_params, JSON_UNESCAPED_UNICODE);
    }

    public function redirto($params)
    {
        $t_id = $params['t_id'];
        $token = $params['token'];
        $pa_id = $params['pa_id'] ?? null;
        $translationsData = $this->transactionService->getTransaction($t_id);
        if (blank($translationsData)) {
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: transaction not found',
            ];
        }
        if ($pa_id) {
            $this->transactionService->updateById($t_id, ['t_xref_pa_id' => $pa_id]);
        }
        $client = $translationsData['t_client'];
        $issuer = $translationsData['t_issuer'];
        $orderId = $translationsData['t_transaction_id'] ?? '';
        $kbank_config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $pa_id);
        $is_live = $kbank_config['common']['env'] == 'live' ? true : false;
        $app_env = $this->isSandBox();
        if (!$this->gatewayService->getClientUseFile()) {
            $host = $kbank_config['config']['host'] ?? '';
            $secret = $kbank_config['config']['secret'] ?? '';
            $mid = $kbank_config['config']['mid'] ?? '';
        } elseif ($is_live && !$app_env) {
            $host = $kbank_config['production']['host'];
            $secret = $kbank_config['production']['secret'];
            $mid = $kbank_config['production']['mid'];
        } else {
            $host = $kbank_config['sandbox']['sandbox_host'];
            $secret = $kbank_config['sandbox']['sandbox_secret'];
            $mid = $kbank_config['sandbox']['sandbox_mid'];
        }
        $header = ['x-api-key: ' . $secret, 'Content-Type: application/json'];
        $params = [
            'amount' => $translationsData['t_amount'],
            'currency' => $translationsData['t_currency'],
            'description' => '',
            'source_type' => 'card',
            'mode' => 'token',
            'token' => $token,
            'reference_order' => $translationsData['t_transaction_id'],
            'additional_data' => [
                'mid' => $mid,
            ],
        ];
        $init_host_url = $host . '/card/v2/charge';
        $chargeResponse = $this->paymentInitiateService->paymentInitiate('post', $init_host_url, json_encode($params), false, $header);
        if (strpos($chargeResponse, 'error') !== false) {
            return ['status' => 'fail', 'content' => $chargeResponse];
        }
        $chargeResponseData = json_decode($chargeResponse, true);
        if (!empty($chargeResponseData['id'])) {
            $this->transactionService->update(['t_transaction_id' => $orderId], ['t_gateway_transaction_id' => $chargeResponseData['id'], 't_gateway' => $this->getPaymentGatewayName()]);
        }

        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $translationsData);

        return [
            'is_success' => $chargeResponseData['status'] != 'success' ? 'error' : 'ok',
            'orderid' => $orderId,
            'issuer' => $translationsData['t_issuer'],
            'amount' => $translationsData['t_amount'],
            'message' => $chargeResponseData['transaction_state'],
            'href' => ($chargeResponseData['transaction_state'] == 'Pre-Authorized' && $chargeResponseData['status'] == 'success') ? $chargeResponseData['redirect_url'] : $translationsData['t_redirect_url'],
        ];
    }

    public function return($return_params)
    {
        $charge_id = $return_params['objectId'];
        $transaction = $this->transactionService->fetchTransaction(['t_gateway_transaction_id' => $charge_id, 't_tech_deleted' => false]);
        if (empty($transaction)) {
            return [
                'is_success' => 'fail',
                'orderid' => $charge_id,
                'message' => 'transaction_id_not_exists',
            ];
        }
        $kbank_config = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName(), $transaction['t_xref_pa_id']);
        $is_live = $kbank_config['common']['env'] == 'live' ? true : false;
        $app_env = $this->isSandBox();
        if (!$this->gatewayService->getClientUseFile()) {
            $host = $kbank_config['config']['host'] ?? '';
            $secret = $kbank_config['config']['secret'] ?? '';
        } elseif ($is_live && !$app_env) {
            $host = $kbank_config['production']['host'];
            $secret = $kbank_config['production']['secret'];
        } else {
            $host = $kbank_config['sandbox']['sandbox_host'];
            $secret = $kbank_config['sandbox']['sandbox_secret'];
        }
        $header = ['x-api-key: ' . $secret, 'Content-Type: application/json'];
        $charge_host = $host . '/card/v2/charge/' . $charge_id;
        $charges_payments = $this->paymentInitiateService->paymentInitiate('get', $charge_host, '', false, $header);
        if (strpos($charges_payments, 'error') !== false) {
            return ['status' => 'fail', 'content' => $charges_payments];
        }
        $charges_payments_data = json_decode($charges_payments, true);

        return [
            'is_success' => $charges_payments_data['status'] == 'success' ? 'ok' : 'error',
            'orderid' => $transaction['t_transaction_id'],
            'issuer' => $transaction['t_issuer'],
            'amount' => $transaction['t_amount'],
            'message' => $charges_payments_data['transaction_state'],
            'href' => $transaction['t_redirect_url'],
        ];
    }
}
