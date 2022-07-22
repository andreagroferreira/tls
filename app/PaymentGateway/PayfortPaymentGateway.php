<?php

namespace App\PaymentGateway;

use App\Services\FormGroupService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\PaymentInitiateService;
use App\Services\TransactionLogsService;
use App\Services\TransactionService;
use App\Services\TransactionItemsService;
use App\Services\ApiService;
use Illuminate\Support\Facades\Log;
use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use Illuminate\Support\Arr;

class PayfortPaymentGateway implements PaymentGatewayInterface
{
    private $transactionLogsService;
    private $transactionService;
    private $transactionItemsService;
    private $formGroupService;
    private $gatewayService;
    private $paymentService;
    private $apiService;

    public function __construct(
        PaymentInitiateService $paymentInitiateService,
        TransactionService $transactionService,
        TransactionLogsService $transactionLogsService,
        TransactionItemsService $transactionItemsService,
        FormGroupService $formGroupService,
        GatewayService $gatewayService,
        PaymentService $paymentService,
        ApiService $apiService
    )
    {
        $this->paymentInitiateService = $paymentInitiateService;
        $this->transactionService     = $transactionService;
        $this->transactionLogsService = $transactionLogsService;
        $this->transactionItemsService = $transactionItemsService;
        $this->formGroupService   = $formGroupService;
        $this->gatewayService     = $gatewayService;
        $this->paymentService     = $paymentService;
        $this->apiService         = $apiService;
    }

    public function getPaymentGatewayName()
    {
        return 'payfort';
    }

    public function isSandBox()
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    public function checkout()
    {

    }

    public function redirto($t_id)
    {
        $translations_data = $this->transactionService->getTransaction($t_id);
        $app_env = $this->isSandBox();
        $client  = $translations_data['t_client'];
        $issuer  = $translations_data['t_issuer'];
        $fg_id   = $translations_data['t_xref_fg_id'];
        $payfort_config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName());
        $pay_config     = $this->getPaySecret($payfort_config, $app_env);
        $application    = $this->formGroupService->fetch($fg_id, $client);
        $u_email        = $application['u_relative_email'] ?? $application['u_email'] ?? "tlspay-{$client}-{$fg_id}@tlscontact.com";
        $params = [
            'command'             => 'PURCHASE',
            'access_code'         => $pay_config['access_code'],
            'merchant_identifier' => $pay_config['merchant_id'],
            'merchant_reference'  => $translations_data['t_transaction_id'],
            'amount'              => $translations_data['t_amount'] * 100,
            'currency'            => $translations_data['t_currency'],
            'language'            => 'en',
            'customer_email'      => $u_email,
            'return_url'          => get_callback_url($payfort_config['common']['return_url']),
        ];
        $params['signature'] = $this->makeSignature($params, $pay_config['request_phrase']);

        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $translations_data);

        return [
            'form_method' => 'post',
            'form_action' => $pay_config['host'],
            'form_fields' => $params
        ];
    }

    public function return($return_params)
    {
        $app_env  = $this->isSandBox();
        $order_id = $return_params['merchant_reference'] ?? '';

        if (empty($order_id)) {
            return [
                'is_success' => 'fail',
                'orderid'    => '[null]',
                'message'    => 'empty_merchant_ref_number'
            ];
        }
        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $order_id, 't_tech_deleted' => false]);
        if (empty($transaction)) {
            return [
                'is_success' => 'fail',
                'orderid'    => $order_id,
                'message'    => 'transaction_id_not_exists'
            ];
        }
        if (strtolower($transaction['t_status']) == 'done') {
            return [
                'is_success' => 'ok',
                'orderid'    => $order_id,
                'message'    => 'The transaction paid successfully.',
                'href'       => $transaction['t_redirect_url']
            ];
        }
        if (strtolower($transaction['t_status']) == 'close') {
            return [
                'is_success' => 'fail',
                'orderid'    => $order_id,
                'message'    => 'transaction_cancelled',
                'href'       => $transaction['t_onerror_url']
            ];
        }
        $payfort_config = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName());
        $pay_config     = $this->getPaySecret($payfort_config, $app_env);
        $validate       = $this->validateSignature($return_params, $pay_config['response_phrase']);
        if ($validate) {
            if ($return_params['amount'] != $transaction['t_amount'] * 100) {
                return [
                    'is_success' => 'fail',
                    'orderid'    => $order_id,
                    'message'    => 'payment_amount_incorrect',
                    'href'       => $transaction['t_onerror_url']
                ];
            }
            if (strtolower($transaction['t_status']) == 'pending' && strtoupper($return_params['command']) == 'PURCHASE' && $return_params['response_code'] == 14000 && $return_params['status'] == 14 && strtolower($return_params['response_message']) == 'success') {
                // update transaction
                $confirm_params = [
                    'gateway'                => $this->getPaymentGatewayName(),
                    'amount'                 => floatval($transaction['t_amount']),
                    'currency'               => $transaction['t_currency'],
                    'transaction_id'         => $transaction['t_transaction_id'],
                    'gateway_transaction_id' => $return_params['fort_id'],
                ];
                $response = $this->paymentService->confirm($transaction, $confirm_params);
                if ($response['is_success'] == 'ok') {
                    return [
                        'is_success' => 'ok',
                        'orderid'    => $order_id,
                        'message'    => 'The transaction paid successfully.',
                        'href'       => $transaction['t_redirect_url']
                    ];
                } else {
                    return [
                        'is_success' => 'fail',
                        'orderid'    => $order_id,
                        'message'    => $response['message'],
                        'href'       => $transaction['t_onerror_url']
                    ];
                }
            } else {
                return [
                    'is_success' => 'fail',
                    'orderid'    => $order_id,
                    'message'    => 'unknown_error',
                    'href'       => $transaction['t_onerror_url']
                ];
            }
        } else {
            return [
                'is_success' => 'fail',
                'orderid'    => $order_id,
                'message'    => 'signature_verification_failed',
                'href'       => $transaction['t_onerror_url']
            ];
        }
    }

    public function notify($notify_params)
    {
        $app_env      = $this->isSandBox();
        $order_id     = $notify_params['merchant_reference'] ?? '';
        $json['code'] = 400;

        if (empty($order_id)) {
            $json['message'] = 'empty_merchant_ref_number';
            return $json;
        }
        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $order_id, 't_tech_deleted' => false]);
        if (empty($transaction)) {
            $json['message'] = 'transaction_id_not_exists';
            return $json;
        }
        if (strtolower($transaction['t_status']) == 'done') {
            $json['code'] = 200;
            $json['message'] = 'transaction_finished';
            return $json;
        }
        if (strtolower($transaction['t_status']) == 'close') {
            $json['message'] = 'transaction_cancelled';
            return $json;
        }
        $payfort_config = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName());
        $pay_config     = $this->getPaySecret($payfort_config, $app_env);
        $validate       = $this->validateSignature($notify_params, $pay_config['response_phrase']);
        if ($validate) {
            if ($notify_params['amount'] != $transaction['t_amount'] * 100) {
                $json['message'] = 'payment_amount_incorrect';
                return $json;
            }

            if (strtolower($transaction['t_status']) == 'pending' && strtoupper($notify_params['command']) == 'PURCHASE' && $notify_params['response_code'] == 14000 && $notify_params['status'] == 14 && strtolower($notify_params['response_message']) == 'success') {
                // update transaction
                $confirm_params = [
                    'gateway'                => $this->getPaymentGatewayName(),
                    'amount'                 => floatval($transaction['t_amount']),
                    'currency'               => $transaction['t_currency'],
                    'transaction_id'         => $transaction['t_transaction_id'],
                    'gateway_transaction_id' => $notify_params['fort_id'],
                ];
                $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $notify_params,'success');
                $response = $this->paymentService->confirm($transaction, $confirm_params);
                if ($response['is_success'] == 'ok') {
                    $json['code'] = 200;
                    $json['message'] = 'transaction_success';
                    return $json;
                } else {
                    $json['message'] = $response['message'];
                    return $json;
                }
            } else {
                $json['message'] = 'unknown_error';
                return $json;
            }
        } else {
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $notify_params,'fail');
            $json['message'] = 'signature_verification_failed';
            return $json;
        }
    }

    private function getPaySecret($pay_config, $app_env) {
        $is_live = ($pay_config['common']['env'] == 'live');
        $key = ($is_live && !$app_env) ? 'prod' : 'sandbox';
        return $pay_config[$key];
    }

    private function makeSignature($arr, $phrase) {
        $shaString = '';
        ksort($arr);
        foreach ($arr as $key => $value) {
            $shaString .= "$key=$value";
        }
        return hash('sha256', $phrase . $shaString . $phrase);
    }

    private function validateSignature($arr, $phrase): bool
    {
        if (!isset($arr['signature'])) {
            return false;
        }
        $old_sign = $arr['signature'];
        $arr      = Arr::except($arr, ['signature']);
        $new_sign = $this->makeSignature($arr, $phrase);
        return $new_sign==$old_sign;
    }
}
