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
        Log::info('Switch redirto $response:'.json_encode($response));
        $this->paymentService->saveTransactionLog($translations_data['t_transaction_id'], $response, $this->getPaymentGatewayName());

        if (array_get($response, 'status') != 200 || blank(array_get($response, 'body.id'))) {
            $this->logWarning('Create checkout failed.', $post_data);
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: payment failed.'
            ];
        }

        if($translations_data['t_status'] == 'pending'){
            $update_fields  = [
                't_gateway_transaction_id' => array_get($response, 'body.id'),
            ];
            $this->transactionService->updateById($translations_data['t_id'], $update_fields);
        }
        return [
            'form_method' => 'load_js',
            'form_action' => $host . '/v1/paymentWidgets.js?checkoutId=' . array_get($response, 'body.id'),
            'form_fields' => [
                'action' => $return_url,
                'class' => 'paymentWidgets',
                'data_brands' => 'VISA MASTER AMEX'// todo, 待定
            ]
        ];
    }

    public function notify($params)
    {
        return true;
    }

    public function return($return_params)
    {
        Log::info('Switch start return:'.json_encode($return_params));
        $t_gateway_transaction_id = $return_params['id'] ?? '';
        $resourcePath = $return_params['resourcePath'];
        if (empty($t_gateway_transaction_id)) {
            return [
                'is_success' => 'fail',
                'gateway_transaction_id' => '[null]',
                'message'    => 'empty_id'
            ];
        }
        $transaction = $this->transactionService->fetchTransaction(['t_gateway_transaction_id' => $t_gateway_transaction_id, 't_tech_deleted' => false]);
        if($transaction){
            $this->paymentService->saveTransactionLog($transaction['t_transaction_id'], $return_params, $this->getPaymentGatewayName());
        }
        if (empty($transaction)) {
            return [
                'is_success' => 'fail',
                'orderid'    => $transaction['t_transaction_id'],
                'message'    => 'transaction_id_not_exists'
            ];
        }
        if (strtolower($transaction['t_status']) == 'done') {
            return [
                'is_success' => 'ok',
                'orderid'    => $transaction['t_transaction_id'],
                'message'    => 'The transaction paid successfully.',
                'href'       => $transaction['t_redirect_url']
            ];
        }
        if (strtolower($transaction['t_status']) == 'close') {
            return [
                'is_success' => 'fail',
                'orderid'    => $transaction['t_transaction_id'],
                'message'    => 'transaction_cancelled',
                'href'       => $transaction['t_onerror_url']
            ];
        }
        $switch_config = $this->getConfig($transaction['t_client'], $transaction['t_issuer']);
        $host = array_get($switch_config, 'current.host');
        $entity_id = array_get($switch_config, 'current.entity_id');
        $response = $this->apiService->callGeneralApi('GET', $host . $resourcePath.'?entityId='.$entity_id, '', $this->getHeaders($switch_config));
        Log::info('Switch return $response:'.json_encode($response));
        $this->paymentService->saveTransactionLog($transaction['t_transaction_id'], $response, $this->getPaymentGatewayName());
        if (array_get($response, 'status') != 200) {
            $this->logWarning('Switch return failed.');
            $this->paymentService->PaymentTransationLog($this->getPaymentGatewayName(),$transaction, $response,'fail');
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: payment failed.'
            ];
        }

        $result_code = array_get($response, 'body.result.code');
        if($result_code == '000.000.000' || $result_code == '000.100.110' ){
            $this->paymentService->PaymentTransationLog($this->getPaymentGatewayName(),$transaction, $response,'success');
            if ($transaction['t_status'] == 'pending') {
                // confirm the elements of the payment
                $confirm_params = [
                    'gateway'                => $this->getPaymentGatewayName(),
                    'amount'                 => floatval($transaction['t_amount']),
                    'currency'               => $transaction['t_currency'],
                    'transaction_id'         => $transaction['t_transaction_id'],
                    'gateway_transaction_id' => $t_gateway_transaction_id,
                ];
                $response_t = $this->paymentService->confirm($transaction, $confirm_params);
                Log::info('Switch return $response_t:'.json_encode($response_t));
                if ($response_t['is_success'] == 'ok') {
                    return [
                        'is_success' => 'ok',
                        'orderid'    => $transaction['t_transaction_id'],
                        'message'    => 'The transaction paid successfully.',
                        'href'       => $transaction['t_redirect_url']
                    ];
                } else {
                    return [
                        'is_success' => 'fail',
                        'orderid'    => $transaction['t_transaction_id'],
                        'message'    => $response['message'],
                        'href'       => $transaction['t_onerror_url']
                    ];
                }
            } else {
                return [
                    'is_success' => 'ok',
                    'orderid'    => $transaction['t_transaction_id'],
                    'message'    => 'Order status error',
                    'href'       => $transaction['t_onerror_url']
                ];
            }
        }elseif($result_code == '000.200.000'){
            $this->paymentService->PaymentTransationLog($this->getPaymentGatewayName(),$transaction, $response,'success');
            return [
                'is_success' => 'ok',
                'orderid'    => $transaction['t_transaction_id'],
                'message'    => 'transaction pending',
                'href'       => $transaction['t_onerror_url']
            ];
        }else{
            $this->paymentService->PaymentTransationLog($this->getPaymentGatewayName(),$transaction, $response,'fail');
            return [
                'is_success' => 'fail',
                'orderid'    => $transaction['t_transaction_id'],
                'message'    => 'unknown_error',
                'href'       => $transaction['t_onerror_url']
            ];
        }


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
