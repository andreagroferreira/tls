<?php

namespace App\PaymentGateway;

use App\Services\ApiService;
use App\Services\FormGroupService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;
use App\Contracts\PaymentGateway\PaymentGatewayInterface;

class SwitchPaymentGateway implements PaymentGatewayInterface
{
    protected $transactionService;
    protected $gatewayService;
    protected $paymentService;
    protected $formGroupService;
    protected $apiService;
    protected $amount_decimals = 2;

    public function __construct(
        TransactionService $transactionService,
        GatewayService     $gatewayService,
        PaymentService     $paymentService,
        FormGroupService   $formGroupService,
        ApiService         $apiService
    )
    {
        $this->transactionService = $transactionService;
        $this->gatewayService = $gatewayService;
        $this->paymentService = $paymentService;
        $this->formGroupService = $formGroupService;
        $this->apiService = $apiService;
    }

    public function getPaymentGatewayName()
    {
        return 'switch';
    }

    public function isSandBox()
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    public function checkout()
    {
        return true;
    }

    public function redirto($t_id)
    {
        $translations_data = $this->transactionService->getTransaction($t_id);
        if (blank($translations_data)) {
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: transaction not found'
            ];
        }

        $switch_config = $this->getConfig($translations_data['t_client'], $translations_data['t_issuer']);
        $return_url = get_callback_url(array_get($switch_config, 'common.return_url'));
        $host = array_get($switch_config, 'current.host');
        $post_data = [
            'entityId' => array_get($switch_config, 'current.entity_id'),
            'amount' => $this->amountFormat($translations_data['t_amount']),
            'currency' => $translations_data['t_currency'],
            'paymentType' => 'DB'
        ];

        $response = $this->apiService->callGeneralApi('POST', $host . '/v1/checkouts', $post_data, $this->getHeaders($switch_config));
        $this->paymentService->saveTransactionLog($translations_data['t_transaction_id'], $response, $this->getPaymentGatewayName());

        if (array_get($response, 'status') != 200 || blank(array_get($response, 'body.id'))) {
            $this->logWarning('Create checkout failed.', $post_data);
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: payment failed.'
            ];
        }

        return [
            'form_method' => 'load_js',
            'form_action' => $host . '/v1/paymentWidgets.js?checkoutId=' . array_get($response, 'body.id'),
            'form_fields' => [
                'action' => $return_url,
                'data-brands' => 'VISA MASTER AMEX'// todo, 待定
            ]
        ];
    }

    public function notify($params)
    {
        return true;
    }

    public function return($params)
    {

    }

    protected function getConfig($client, $issuer)
    {
        $app_env = $this->isSandBox();
        $config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName());
        $is_live = $config['common']['env'] == 'live' ? true : false;
        if ($is_live && !$app_env) {
            // Live account
            $config['current'] = $config['prod'];
        } else {
            // Test account
            $config['current'] = $config['sandbox'];
        }

        return $config;
    }

    protected function getHeaders(array $config)
    {
        return [
            'Authorization' => 'Bearer ' . array_get($config, 'current.access_token'),
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'
        ];
    }

    protected function amountFormat($amount)
    {
        return number_format($amount, $this->amount_decimals, '.', '');
    }

    protected function logWarning($message, $params)
    {
        Log::warning('ONLINE PAYMENT, ' . $this->getPaymentGatewayName() . ' ' . $message);
        Log::warning(json_encode($params, JSON_UNESCAPED_UNICODE));
    }
}
