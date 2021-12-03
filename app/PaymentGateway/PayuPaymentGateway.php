<?php

namespace App\PaymentGateway;

use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\PaymentInitiateService;
use App\Services\TransactionLogsService;
use App\Services\TransactionService;
use App\Services\TransactionItemsService;
use App\Services\ApiService;
use Illuminate\Support\Facades\Log;
use App\Contracts\PaymentGateway\PaymentGatewayInterface;

class PayuPaymentGateway implements PaymentGatewayInterface
{
    private $transactionLogsService;
    private $transactionService;
    private $transactionItemsService;
    private $gatewayService;
    private $paymentService;
    private $apiService;

    public function __construct(
        PaymentInitiateService $paymentInitiateService,
        TransactionService $transactionService,
        TransactionLogsService $transactionLogsService,
        TransactionItemsService $transactionItemsService,
        GatewayService $gatewayService,
        PaymentService $paymentService,
        ApiService $apiService
    )
    {
        $this->paymentInitiateService = $paymentInitiateService;
        $this->transactionService     = $transactionService;
        $this->transactionLogsService = $transactionLogsService;
        $this->transactionItemsService = $transactionItemsService;
        $this->gatewayService     = $gatewayService;
        $this->paymentService     = $paymentService;
        $this->apiService         = $apiService;
    }

    public function getPaymentGatewayName()
    {
        return 'payu';
    }

    public function isSandBox()
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    public function checkout()
    {

    }

    public function notify($params)
    {
        return 'notify';
    }

    public function redirto($t_id)
    {
        $translationsData = $this->transactionService->getTransaction($t_id);
        $app_env = $this->isSandBox();
        $client  = $translationsData['t_client'];
        $issuer  = $translationsData['t_issuer'];
        $orderId = $translationsData['t_transaction_id'] ?? '';
        $payu_config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName());
        $paymentsos_host = $payu_config['common']['paymentsos_host'];
        $payment_method  = $payu_config['common']['payment_method'];
        $header = $this->getHeader($payu_config, $app_env);
        // create payment
        $create_params = array(
            'amount'   => $translationsData['t_amount'] * 100,
            'currency' => $translationsData['t_currency'] ?? $payu_config['common']['currency'],
            'order'    => ['id' => $orderId]
        );
        $payments = $this->paymentInitiateService->paymentInitiate('post', $paymentsos_host, json_encode($create_params), false, $header);
        if (strpos($payments,'error') !== false) {return ['status' => 'fail', 'content' => $payments]; }
        // charges payment
        $payment_id = json_decode($payments, true)['id'];
        $paymentsos_charges_host = $paymentsos_host . '/' . $payment_id . '/charges';
        $charges_params = array(
            'payment_method' => [
                'source_type' => 'payment_page',
                'type'        => 'untokenized',
                'additional_details' => ['supported_payment_methods' => $payment_method]
            ],
            'merchant_site_url' => get_callback_url($payu_config['common']['return_url'])
        );
        $charges_payments = $this->paymentInitiateService->paymentInitiate('post', $paymentsos_charges_host, json_encode($charges_params), false, $header);
        if (strpos($charges_payments,'error') !== false) {return ['status' => 'fail', 'message' => $charges_payments]; }
        $charges_payments = json_decode($charges_payments, true);
        if (!empty($charges_payments['id'])) {
            $this->transactionService->updateById($t_id, ['t_gateway_transaction_id' => $charges_payments['id']]);
        }
        $payment_page_url = $charges_payments['redirection']['url'];
        $payUReference    = $charges_payments['provider_data']['transaction_id'];
        return [
            'form_method' => 'get',
            'form_action' => $payment_page_url,
            'form_fields' => ['PayUReference' => $payUReference]
        ];
    }

    public function return($return_params)
    {
        $app_env    = $this->isSandBox();
        $payment_id = $return_params['payment_id'] ?? '';
        $charge_id  = $return_params['charge_id'] ?? '';
        $transaction = $this->transactionService->fetchTransaction(['t_gateway_transaction_id' => $charge_id, 't_tech_deleted' => false]);
        if (empty($transaction)) {
            Log::warning("ONLINE PAYMENT, PAYU : No transaction found in the database for " . $charge_id . "\n" .
                json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return [
                'status'  => 'fail',
                'message' => 'Transaction ERROR: transaction not found'
            ];
        }
        $payu_config = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName());
        $charges_host = $payu_config['common']['paymentsos_host'] . '/' . $payment_id . '/charges/' . $charge_id;
        $charges_payments = $this->paymentInitiateService->paymentInitiate('get', $charges_host, '', false, $this->getHeader($payu_config, $app_env));
        if (strpos($charges_payments,'error') !== false) {return ['status' => 'fail', 'message' => $charges_payments]; }
        $charges_payments = json_decode($charges_payments, true);
        if ($charges_payments['result']['status'] == 'Succeed') {
            $confirm_params = [
                'gateway'                => $this->getPaymentGatewayName(),
                'amount'                 => $charges_payments['amount'] / 100,
                'currency'               => $transaction['t_currency'],
                'transaction_id'         => $transaction['t_transaction_id'],
                'gateway_transaction_id' => $charge_id,
            ];
            return $this->paymentService->confirm($transaction, $confirm_params);
        } else {
            return array(
                'is_success' => 'error',
                'orderid'    => $transaction['t_transaction_id'],
                'issuer'     => $transaction['t_issuer'],
                'amount'     => $charges_payments['amount'] / 100,
                'message'    => $charges_payments['result']['status'],
                'href'       => $transaction['t_redirect_url']
            );
        }
    }

    public function getHeader($payu_config, $app_env) {
        $is_live        = $payu_config['common']['env'] == 'live' ? true : false;
        if ($is_live && !$app_env) {
            // Live account
            $app_id          = $payu_config['prod']['app_id'];
            $private_key     = $payu_config['prod']['private_key'];
            $api_version     = $payu_config['prod']['api_version'];
            $payments_os_env = $payu_config['prod']['payments_os_env'];
        } else {
            // Test account
            $app_id          = $payu_config['sandbox']['sandbox_app_id'];
            $private_key     = $payu_config['sandbox']['sandbox_private_key'];
            $api_version     = $payu_config['sandbox']['sandbox_api_version'];
            $payments_os_env = $payu_config['sandbox']['sandbox_payments_os_env'];
        }
        return array(
            'app_id: ' . $app_id,
            'private_key: ' . $private_key,
            'api-version: ' . $api_version,
            'x-payments-os-env: ' . $payments_os_env,
            'Content-Type: application/json'
        );
    }
}
