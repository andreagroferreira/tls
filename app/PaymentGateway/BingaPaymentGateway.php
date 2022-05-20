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

class BingaPaymentGateway implements PaymentGatewayInterface
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
        return 'binga';
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
        $orderid = $translations_data['t_transaction_id'] ?? '';
        $amount = $translations_data['t_amount'];
        $client  = $translations_data['t_client'];
        $issuer  = $translations_data['t_issuer'];
        $fg_id   = $translations_data['t_xref_fg_id'];
        $payfort_config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName());
        $pay_config     = $this->getPaySecret($payfort_config, $app_env);
        $application    = $this->formGroupService->fetch($fg_id, $client);
        $cai = $application['f_cai'] ?? 'binga';
        $gateway     = $pay_config['gateway'];
        $private_key = $pay_config['private_key'];
        $default_params = [
            "out_trade_no" => $orderid,
            "product_code" => $payfort_config['common']['product_code'],
            "total_amount" => number_format($amount, 2, ".", ""),
            "subject" => substr($cai, 0, 256)
        ];
        $post_params = [
            'app_id'      => $pay_config['app_id'],
            'method'      => $payfort_config['common']['method'],
            'format'      => 'json',
            'return_url'  => get_callback_url($payfort_config['common']['return_url']),
            'charset'     => 'UTF-8',
            'sign_type'   => 'RSA2',
            'sign'        => '',
            'timestamp'   => date('Y-m-d H:i:s'),
            'version'     => '1.0',
            'notify_url'  => get_callback_url($payfort_config['common']['notify_url']),
            'biz_content' => json_encode($default_params, JSON_UNESCAPED_UNICODE),
        ];
        $search = [
            "-----BEGIN RSA PRIVATE KEY-----",
            "-----END RSA PRIVATE KEY-----",
            "-----BEGIN PUBLIC KEY-----",
            "-----END PUBLIC KEY-----",
            "\n",
            "\r",
            "\r\n"
        ];
        unset($post_params['sign']);
        ksort($post_params);
        //dd($post_params);
        $tmp = urldecode(http_build_query($post_params));
        $private_res = $search[0] . PHP_EOL . wordwrap($private_key, 64, "\n", true) . PHP_EOL . $search[1];
        // 生成的签名
        $private_sign = openssl_sign($tmp, $sign, $private_res, OPENSSL_ALGO_SHA256) ? base64_encode($sign) : null;
        //$post_params['sign'] = $private_sign;
        $post_params['sign'] = $private_sign;
        $post_params['sign_type'] = "RSA2";
        $gatewayURL = $gateway . "?charset=UTF-8";
        //$post_params['biz_content'] = $default_params;
        ksort($post_params);
        //$parms = http_build_query($post_params);
        //var_dump($parms);exit;
        return [
            'form_method' => 'post',
            'form_action' => $gatewayURL,
            'form_fields' => $post_params
        ];
    }

    public function return($return_params)
    {
        $app_env  = $this->isSandBox();
        Log::info('binga start return:'.json_encode($return_params));
        $order_id = $return_params['out_trade_no'] ?? '';
        $app_id = $return_params['app_id'];
        $sign = $return_params['sign'] ?? '';
        if (empty($order_id)) {
            return [
                'is_success' => 'fail',
                'orderid'    => '[null]',
                'message'    => 'empty_out_trade_no'
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
        ##signature verification start
        $sign_params = [];
        foreach($return_params as $key => $value) {
            if (!in_array($key, ['sign',  'sign_type', '']) && !empty($value)) {
                $sign_params[$key] = $key  . '=' . $value;
            }
        }
        $binga_appid = $pay_config['app_id'];
        $public_key = $pay_config['public_key'];
        ksort($sign_params);
        $tmp = implode('&', $sign_params);
        Log::info('binga return $tmp:'.$tmp);
        //exit;
        if (!empty($app_id) && $app_id == $binga_appid) {
            $public_res    = "-----BEGIN PUBLIC KEY-----" . PHP_EOL .  wordwrap($public_key, 64, "\n", true) . PHP_EOL . "-----END PUBLIC KEY-----";
            // signature
            $expected_sign = !(openssl_verify($tmp, base64_decode($sign), $public_res, OPENSSL_ALGO_SHA256)===1);
            Log::info('binga return $expected_sign:'.$expected_sign);
            //exit;
            //$expected_sign = false;
            // signature verification success
            if ($expected_sign == false) {
                Log::info('binga return signature verification success！');
                $received_currency = isset($return_params['receipt_currency_type']) ? $return_params['receipt_currency_type'] : 'RMB';##currency,receipt_currency_type
                $received_amount = isset($return_params['total_amount']) ? $return_params['total_amount'] : '0';

                $expected_amount   = strval($transaction['t_amount']);
                $expected_currency = $transaction['t_currency'];
                // security check #2 : amount and currency
                $amount_matched   = ($expected_amount   == $received_amount);
                $currency_matched = ($expected_currency == $received_currency);
                if (!$amount_matched or !$currency_matched) {
                    Log::warning("binga return: data check failed-1 : ($expected_amount == $received_amount) ($expected_currency == $received_currency)");
                    Log::warning("binga return: data check failed-2 : ". print_r($return_params, 'error'));
                    return [
                        'is_success' => 'fail',
                        'orderid'    => $order_id,
                        'message'    => 'data check failed',
                        'href'       => $transaction['t_onerror_url']
                    ];
                }
                if ($transaction['t_status'] == 'pending') {
                    // confirm the elements of the payment
                    $confirm_params = [
                        'gateway'                => $this->getPaymentGatewayName(),
                        'amount'                 => floatval($transaction['t_amount']),
                        'currency'               => $transaction['t_currency'],
                        'transaction_id'         => $transaction['t_transaction_id'],
                        'gateway_transaction_id' => $return_params['out_trade_no'],
                    ];
                    $response = $this->paymentService->confirm($transaction, $confirm_params);
                    Log::info('binga return $response:'.json_encode($response));
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
                        'is_success' => 'ok',
                        'orderid'    => $order_id,
                        'message'    => 'unknown_error',
                        'href'       => $transaction['t_onerror_url']
                    ];
                }
            } else { ##signature verification fail
                return [
                    'is_success' => 'fail',
                    'orderid'    => $order_id,
                    'message'    => 'signature_verification_failed',
                    'href'       => $transaction['t_onerror_url']
                ];
            }
        }

    }

    public function notify($notify_params)
    {
        $transaction_error = true;
        $app_env = $this->isSandBox();
        $order_id = $notify_params['out_trade_no'] ?? '';
        Log::info('binga start notify:'.json_encode($notify_params));
        $app_id = $notify_params['app_id'];
        $sign = $notify_params['sign'];
        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $order_id, 't_tech_deleted' => false]);
        if($transaction){
            $this->transactionLogsService->create(['tl_xref_transaction_id' => $transaction['t_transaction_id'], 'tl_content' =>json_encode($notify_params)]);
        }
        if (empty($order_id)) {
            $msg = 'empty_out_trade_no';
            return $msg;
        }
        if (empty($transaction)) {
            $msg = 'transaction_id_not_exists';
            return $msg;
        }
        if (strtolower($transaction['t_status']) == 'done') {
            $msg = 'success';
            Log::info("binga notify done");
            return $msg;
        }
        if (strtolower($transaction['t_status']) == 'close') {
            $msg = 'transaction_cancelled';
            return $msg;
        }
        $payfort_config = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName());
        $pay_config     = $this->getPaySecret($payfort_config, $app_env);
        ##signature verifired start
        $sign_params = [];
        foreach($notify_params as $key => $value) {
            if (!in_array($key, ['sign',  'sign_type', '']) && !empty($value)) {
                $sign_params[$key] = $key  . '=' . $value;
            }
        }
        $binga_appid = $pay_config['app_id'];
        $public_key = $pay_config['public_key'];
        ksort($sign_params);
        $tmp = implode('&', $sign_params);
        Log::info('binga notify $tmp:'.$tmp);
        $expected_sign = true;
        if (!empty($app_id) && $app_id == $binga_appid) {
            $public_res    = "-----BEGIN PUBLIC KEY-----" . PHP_EOL .  wordwrap($public_key, 64, "\n", true) . PHP_EOL . "-----END PUBLIC KEY-----";
            // signature verifired
            $expected_sign = !(openssl_verify($tmp, base64_decode($sign), $public_res, OPENSSL_ALGO_SHA256)===1);
            // signature verifired success
            if ($expected_sign == false) {
                Log::info('binga notify signature verifired success！');
                $received_currency = isset($notify_params['receipt_currency_type']) ? $notify_params['receipt_currency_type'] : 'RMB';##currency,receipt_currency_type
                $received_amount = isset($notify_params['total_amount']) ? $notify_params['total_amount'] : '0';

                $expected_amount   = strval($transaction['t_amount']);
                $expected_currency = $transaction['t_currency'];
                // security check #2 : amount and currency
                $amount_matched   = ($expected_amount   == $received_amount);
                $currency_matched = ($expected_currency == $received_currency);
                if (!$amount_matched or !$currency_matched) {
                    Log::warning("binga notify: data check failed-1 : ($expected_amount == $received_amount) ($expected_currency == $received_currency)");
                     Log::warning("binga notify: data check failed-2 : ". print_r($notify_params, 'error'));
                    //$transaction_error = true;
                    return "failure";
                }
                $trade_status = $notify_params['trade_status'];
                if ($trade_status == 'TRADE_SUCCESS' && $transaction['t_status'] == 'pending') {
                    // confirm the elements of the payment
                    $confirm_params = [
                        'gateway'                => $this->getPaymentGatewayName(),
                        'amount'                 => floatval($transaction['t_amount']),
                        'currency'               => $transaction['t_currency'],
                        'transaction_id'         => $transaction['t_transaction_id'],
                        'gateway_transaction_id' => $notify_params['out_trade_no'],
                    ];
                    $response = $this->paymentService->confirm($transaction, $confirm_params);
                    Log::info('binga notify $response:'.json_encode($response));
                    if ($response['is_success'] == 'ok') {
                        $json['code'] = 200;
                        $json['message'] = 'transaction_success';
                        Log::info('binga notify payment succeed, status updated！');
                    } else {
                        $json['message'] = $response['message'];
                        Log::info('binga notify payment succeed, failed to update status！');
                    }
                    $transaction_error = false;
                } else if ($trade_status == 'TRADE_FINISHED'){
                    $transaction_error = false;
                    Log::info('tbinga notify ransaction done！');
                }
            } else { ##signature verified failure
                $transaction_error = true;
                Log::info('binga notify signature verified failure！');
            }
        }
        Log::info("binga notify done");
        if ($expected_sign) {
            //$transaction_error = true;
            Log::warning("binga notify: digital signature check failed : ". print_r($notify_params, 'error'));
            $msg = 'failure';
            return $msg;
        }

        if ($transaction_error) {
            return "failure";
        } else {
            return "success";
        }
    }

    private function getPaySecret($pay_config, $app_env) {
        $is_live = ($pay_config['common']['env'] == 'live');
        $key = ($is_live && !$app_env) ? 'prod' : 'sandbox';
        return $pay_config[$key];
    }
}
