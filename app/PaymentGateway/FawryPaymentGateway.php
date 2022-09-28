<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\FormGroupService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionLogsService;
use App\Services\TransactionService;
use App\Services\TransactionItemsService;
use App\Services\ApiService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Jobs\FawryPaymentJob;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

class FawryPaymentGateway implements PaymentGatewayInterface
{
    private $transactionService;
    private $transactionLogsService;
    private $transactionItemsService;
    private $formGroupService;
    private $gatewayService;
    private $paymentService;
    private $apiService;

    public function __construct(
        TransactionService $transactionService,
        TransactionLogsService $transactionLogsService,
        TransactionItemsService $transactionItemsService,
        FormGroupService $formGroupService,
        GatewayService $gatewayService,
        PaymentService $paymentService,
        ApiService $apiService
    ) {
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
        return 'fawry';
    }

    public function isSandBox()
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    public function checkout()
    {

    }

    private function getEnvpayValue($env_key) {
        $suffix = 'ENVPAY_';
        if (strtoupper(substr($env_key, 0, 7)) !== $suffix) {
            return getenv($env_key);
        }
        return getenv(substr($env_key, 7));
    }

    public function redirto($params)
    {
        $t_id = $params['t_id'];
        $lang = $params['lang'] ?? 'en-gb';
        $translations_data = $this->transactionService->getTransaction($t_id);
        $app_env        = $this->isSandBox();
        $client         = $translations_data['t_client'];
        $issuer         = $translations_data['t_issuer'];
        $fg_id          = $translations_data['t_xref_fg_id'];
        $order_id       = $translations_data['t_transaction_id'] ?? '';
        $t_service      = $translations_data['t_service'] ?? 'tls';
        $application    = $this->formGroupService->fetch($fg_id, $client);
        $u_email        = $application['u_relative_email'] ?? $application['u_email'] ?? "tlspay-{$client}-{$fg_id}@tlscontact.com";
        $payment_config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $t_service);
        $is_live        = $payment_config['common']['env'] == 'live' ? true : false;
        if ($is_live && !$app_env) {
            // Live account
            $host_url    = $payment_config['prod']['host'];
            $merchant_id = $this->getEnvpayValue($payment_config['prod']['merchant_id']);
            $secret      = $this->getEnvpayValue($payment_config['prod']['secret_key']);
        } else {
            // Test account
            $host_url    = $payment_config['sandbox']['host'];
            $merchant_id = $this->getEnvpayValue($payment_config['sandbox']['merchant_id']);
            $secret      = $this->getEnvpayValue($payment_config['sandbox']['secret_key']);
        }
        $init_url = url($host_url . '/ECommerceWeb/api/payments/init');
        $return_url = get_callback_url($payment_config['common']['return_url']);
        $pay_version = strtolower($payment_config['common']['version']);
        $pay_js = url($host_url . $payment_config['common']['redirect_path_' . $pay_version]);

        // Create the parameters to generate signature
        $charge_items = [];
        $quantity = '';
        $sku_list = '';
        $profile_id = $application['fg_xref_u_id'] ?? $application['u_id'];
        $items = $this->transactionItemsService->fetchItemsByTransactionId($order_id);

        if (!empty($items)) {
            $items = json_decode($items, true);
            if (json_last_error()) {
                return ['status' => 'fail', 'error' => 'INTERNAL ERROR', 'msg' => 'Transaction items can`t be parsed.'];
            }
            array_multisort(array_column($items,'f_id'), SORT_ASC, $items);
            foreach ($items as $k => $item) {
                $tmp = [];
                $tmp['itemId'] = $item['f_id'];
                $price = 0;
                foreach($item['skus'] as $sku) {
                    $price += floatval($sku['price']);
                    $sku_list .= $sku['sku'];
                }
                $tmp['price'] = $price;
                $tmp['quantity'] = 1;
                $quantity .= $tmp['itemId'] . $tmp['quantity'] . number_format($price, 2, '.', '');
                array_push($charge_items, $tmp);
            }
        } else {
            return ['status' => 'fail', 'error' => 'INTERNAL ERROR', 'msg' => 'Transaction items not found.'];
        }

        $expiry = strtotime("+1 day", strtotime($translations_data['t_expiration'])) * 1000;
        $amount = number_format($translations_data['t_amount'], 2, '.', '');

        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $translations_data);

        if ($pay_version == 'v1') {
            $sign_string = $merchant_id . $order_id . $sku_list . $quantity . $amount . $expiry . $secret;
            $hash_sign = hash('SHA256', $sign_string);
            $params = [
                'language' => 'en-gb',
                'merchant_code' => $merchant_id,
                'merchant_ref_no' => $order_id,
                'u_email' => '',
                'u_name' => '',
                'u_mobile' => '',
                'customerProfileId' => $profile_id,
                'description' => 'create_direct_pay_by_user',
                'expiry' => '',
                'return_url' => $return_url,
                'currency' => $translations_data['t_currency'],
                'total_fee' => $amount,
                'quantity' => 1,
                'productSKU' => $sku_list,
                'sign_type' => 'SHA256',
                'signature' => $hash_sign
            ];
            return [
                'form_method' => 'load_js',
                'form_action' => $pay_js,
                'form_fields' => $params
            ];
        } else {
            $sign_string = $merchant_id . $order_id . $profile_id . $return_url . $quantity . $secret;
            $hash_sign = hash('SHA256', $sign_string);
            // Prepare parameters to post payment gateway -- V2
            // official document: https://developer.fawrystaging.com/docs/express-checkout/self-hosted-checkout
            $params = [
                'merchantCode' => $merchant_id,
                'merchantRefNum' => $order_id,
                'customerEmail' => $u_email,
                'customerProfileId' => $profile_id,
                'paymentExpiry' => $expiry,
                'chargeItems' => $charge_items,
                'returnUrl' => $return_url,
                'authCaptureModePayment' => false,
                'signature' => $hash_sign
            ];

            $client = new Client();
            $response = $client->post($init_url, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Content-Type' => "application/json;charset=utf-8",
                ],
                'json' => $params
            ]);
            $status_code = $response->getStatusCode();
            $payment_id = $response->getBody()->getContents();
            if ($status_code==200 && $payment_id) {
                if (filter_var($payment_id, FILTER_VALIDATE_URL)) {
                    parse_str(parse_url($payment_id, PHP_URL_QUERY), $query);
                    $payment_id = array_get($query, 'payment-id');
                }
                return [
                    'form_method' => 'get',
                    'form_action' => $host_url . '/atfawry/plugin/',
                    'form_fields' => [
                        'payment-id' => $payment_id,
                        'locale' => 'en',
                        'mode' => 'SEPARATED'
                    ],
                ];
            } else {
                return ['status' => 'fail', 'error' => 'INTERNAL ERROR', 'msg' => 'Payment request failed.'];
            }
        }
    }

    public function return($params)
    {
        $payment_config = $this->getPaymentConfig($params);
        $pay_version = strtolower($payment_config['common']['version']);
        return $pay_version == 'v1' ? $this->returnV1($params) : $this->returnV2($params);
    }

    private function getPaymentConfig($params) {
        $order_id  = isset($params['merchantRefNum']) ? $params['merchantRefNum'] : $params['merchantRefNumber'];
        $reg = '/[a-z]{2}[A-Z]{3}2[a-z]{2}/';
        preg_match($reg, $order_id, $matches);
        if (empty($matches)) {
            return [
                'is_success' => 'fail',
                'orderid'    => '[null]',
                'message'    => 'Invalid request'
            ];
        }
        $issuer = $matches[0];
        $client = substr($issuer,-2);
        return $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName());
    }

    private function returnV1($params)
    {
        $order_id = '';
        if (!isset($params['chargeResponse']) && isset($params['merchantRefNum'])) {
            $order_id = $params['merchantRefNum'];
        } else {
            $charge_response = json_decode($params['chargeResponse'], true);
            $order_id = $charge_response['merchantRefNumber'];
        }
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
        $t_service      = $transaction['t_service'] ?? 'tls';
        $payment_config = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName(), $t_service);
        $is_live = $payment_config['common']['env'] == 'live';
        $app_env = $this->isSandBox();
        if ($is_live && !$app_env) {
            // Live account
            $host_url    = $payment_config['prod']['host'];
            $merchant_id = $this->getEnvpayValue($payment_config['prod']['merchant_id']);
            $secret      = $this->getEnvpayValue($payment_config['prod']['secret_key']);
        } else {
            // Test account
            $host_url    = $payment_config['sandbox']['host'];
            $merchant_id = $this->getEnvpayValue($payment_config['sandbox']['merchant_id']);
            $secret      = $this->getEnvpayValue($payment_config['sandbox']['secret_key']);
        }
        $verify_url = url($host_url . $payment_config['common']['verify_path_v1']);
        $verify_params = [];
        $verify_params['merchantCode']      =  $merchant_id;
        $verify_params['merchantRefNumber'] =  $order_id;
        $verify_params['signature']         =  hash('SHA256', $merchant_id . $order_id . $secret);
        $query_params = [];
        foreach ($verify_params as $k => $v) {
            $query_params[] = "$k=$v";
        }
        $client = new Client();
        try {
            $verify_url .= '?' . implode('&', $query_params);
            $response = $client->request('GET', $verify_url);
            $status_code    = $response->getStatusCode();
            $return_content = $response->getBody()->getContents();
            $result         = json_decode($return_content, true);
            if ($status_code == 200 && !empty($result)) {
                if ($result['statusCode'] !== 200) {
                    Log::warning('Request API: ' . $verify_url . ', response content: ' . $return_content);
                    return [
                        'is_success' => 'fail',
                        'orderid'    => $order_id,
                        'message'    => $result['statusDescription'],
                        'href'       => $transaction['t_redirect_url']
                    ];
                }
                $order_status       = $result['paymentStatus'];
                $received_amount    = number_format($result['paymentAmount'], 2, '.', '');
                $transaction_amount = number_format($transaction['t_amount'], 2, '.', '');
                if ($order_status == 'PAID' && $received_amount != $transaction_amount) {
                    Log::warning('The payment amount is inconsistent, merchantRefNumber: ' . $order_id . ' [Received: ' . $received_amount . ', Expected: ' . $transaction_amount .']');
                    return [
                        'is_success' => 'fail',
                        'orderid'    => $order_id,
                        'message'    => 'payment_amount_incorrect',
                        'href'       => $transaction['t_redirect_url']
                    ];
                }
                if ($order_status == 'PAID' && $received_amount == $transaction_amount && $transaction['t_transaction_id'] == $order_id && $transaction['t_status'] == 'pending') {
                    // update transaction
                    $confirm_params = [
                        'gateway'        => $this->getPaymentGatewayName(),
                        'amount'         => floatval($transaction['t_amount']),
                        'currency'       => $transaction['t_currency'],
                        'transaction_id' => $transaction['t_transaction_id'],
                        'gateway_transaction_id' => '',
                    ];
                    $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'success');
                    $response = $this->paymentService->confirm($transaction, $confirm_params);
                    if ($response['is_success'] == 'ok') {
                        return [
                            'is_success' => 'ok',
                            'orderid'    => $order_id,
                            'message'    => 'transaction_has_been_paid_already',
                            'href'       => $transaction['t_redirect_url']
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'fail');
            return [
                'is_success' => 'fail',
                'orderid'    => '400',
                'href'       => '', // TBC
                'message'    => 'unknown_error'
            ];
        }

        return [
            'is_success' => 'fail',
            'orderid'    => $order_id,
            'message'    => 'transaction_has_been_paid_already',
            'href'       => $transaction['t_redirect_url']
        ];
    }

    private function returnV2($params)
    {
        $status_code  = $params['statusCode'] ?? '';
        $order_status = $params['orderStatus'] ?? '';
        $order_id     = $params['merchantRefNumber'] ?? '';
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

        if (strtolower($transaction['t_status']) == 'pending' && $transaction['t_transaction_id'] == $order_id && $status_code == 200 && $order_status == 'PAID') {
            // update transaction
            $confirm_params = [
                'gateway'        => $this->getPaymentGatewayName(),
                'amount'         => floatval($transaction['t_amount']),
                'currency'       => $transaction['t_currency'],
                'transaction_id' => $transaction['t_transaction_id'],
                'gateway_transaction_id' => '',
            ];
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'success');
            $response = $this->paymentService->confirm($transaction, $confirm_params);
            if ($response['is_success'] == 'ok') {
                return [
                    'is_success' => 'ok',
                    'orderid'    => $order_id,
                    'message'    => 'The transaction paid successfully.',
                    'href'       => $transaction['t_redirect_url']
                ];
            }
        }

        return [
            'is_success' => 'fail',
            'orderid'    => $order_id,
            'message'    => 'transaction_has_not_been_paid',
            'href'       => $transaction['t_redirect_url']
        ];
    }

    public function notify($params)
    {
        $order_id = !empty($params['MerchantRefNo']) ? $params['MerchantRefNo'] : $params['merchantRefNumber'];
        if (!$order_id) {
            Log::warning("ONLINE PAYMENT, FAWRY: notify check failed : merchantRefNumber is empty");
            return [
                'status' => 'fail',
                'message' => "empty_merchant_ref_number",
            ];
        }

        // find transaction in database
        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $order_id, 't_tech_deleted' => false]);
        if (empty($transaction)) {
            Log::warning("ONLINE PAYMENT, FAWRY: notify check failed : the transaction number $order_id does not exist");
            return [
                'status' => 'fail',
                'message' => "transaction_id_not_exists",
            ];
        }
        if (strtolower($transaction['t_status']) == 'close') {
            Log::warning("ONLINE PAYMENT, FAWRY: notify check failed : the transaction number $order_id has been canceled");
            return [
                'status' => 'fail',
                'message' => "transaction_cancelled",
            ];
        }
        if (strtolower($transaction['t_status']) == 'done') {
            return [
                'status' => 'success',
            ];
        }

        //get trade information
        $t_service      = $transaction['t_service'] ?? 'tls';
        $payment_config = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName(), $t_service);
        $app_env = $this->isSandBox();
        $is_live = $payment_config['common']['env'] == 'live' ? true : false;
        if ($is_live && !$app_env) {
            // Live config
            $host_url    = $payment_config['prod']['host'];
            $merchant_id = $this->getEnvpayValue($payment_config['prod']['merchant_id']);
            $secret      = $this->getEnvpayValue($payment_config['prod']['secret_key']);
        } else {
            // Test config
            $host_url    = $payment_config['sandbox']['host'];
            $merchant_id = $this->getEnvpayValue($payment_config['sandbox']['merchant_id']);
            $secret      = $this->getEnvpayValue($payment_config['sandbox']['secret_key']);
        }
        if (strtolower($payment_config['common']['version']) == 'v1') {
            $fawry_ref_no      = $params['FawryRefNo'] ?? '';
            $payment_amount    = $params['Amount'] ?? '';
            $order_status      = $params['OrderStatus'] ?? '';
            $message_signature = $params['MessageSignature'] ?? '';
            // verify the signature
            $sign_string = $secret . $payment_amount . $fawry_ref_no . $order_id . $order_status;
        } else {
            $fawry_ref_number  = $params['fawryRefNumber'] ?? '';
            $payment_amount    = sprintf("%.2f", $params['paymentAmount']) ?? '';
            $order_amount      = sprintf("%.2f", $params['orderAmount']) ?? '';
            $order_status      = $params['orderStatus'] ?? '';
            $payment_method    = $params['paymentMethod'] ?? '';
            $message_signature = $params['messageSignature'] ?? '';
            $payment_refrence_number = $params['paymentRefrenceNumber'] ?? '';
            // verify the signature
            $sign_string = $fawry_ref_number . $order_id . $payment_amount . $order_amount . $order_status . $payment_method . $payment_refrence_number . $secret;
        }

        $hash_sign = hash('SHA256', $sign_string);
        if ($hash_sign !== $message_signature) {
            Log::warning("ONLINE PAYMENT, FAWRY: notify check failed : signature verification failed");
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'fail');
            return [
                'status' => 'fail',
                'message' => "signature_verification_failed",
            ];
        }
        // check payment amount
        $t_amount = floatval($transaction['t_amount']);
        if ($t_amount != $payment_amount) {
            Log::warning("ONLINE PAYMENT, FAWRY: notify check failed : payment amount is incorrect");
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'fail');
            return [
                'status' => 'fail',
                'message' => "payment_amount_incorrect",
            ];
        }

        if ($order_status != 'PAID') {
            $status_sign   = $merchant_id . $order_id . $secret;
            $status_url    = $host_url . '/ECommerceWeb/Fawry/payments/status/v2';
            $status_params = [
                'merchantCode'      => $merchant_id,
                'merchantRefNumber' => $order_id,
                'signature'         => hash('SHA256', $status_sign)
            ];
            $query_params = [];
            foreach ($status_params as $k => $v) {
                $query_params[] = "$k=$v";
            }
            $status_url .= '?' . implode('&', $query_params);
            $client = new Client();
            $response = $client->request('GET', $status_url);
            $status_code    = $response->getStatusCode();
            $return_content = $response->getBody()->getContents();
            $result         = json_decode($return_content, true);
            if ($status_code == 200 && !empty($result)) {
                if ($result['orderStatus'] != 'PAID') {
                    $queue_status = false;
                    if ($transaction['t_in_queue'] == true) {
                        if (isset($params['queueStatus'])) { $queue_status = true; }
                    } else {
                        $this->transactionService->update(['t_transaction_id' => $order_id], ['t_in_queue' => true]);
                        $queue_status = true;
                    }
                    if ($queue_status) {
                        $params['queueStatus'] = true;
                        $fawry_job = new FawryPaymentJob($params);
                        dispatch($fawry_job->delay(Carbon::now()->addMinute(1)))->onConnection('tlscontact_fawry_payment_queue')->onQueue('tlscontact_fawry_payment_queue');
                    }
                    Log::warning('ONLINE PAYMENT, Fawrypay(order status is wrong): The current order status is ' . $order_status);
                    $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'fail');
                    return [
                        'status' => 'fail',
                        'message' => 'unknown_error',
                    ];
                }
            }
        }
        // if orderStatus is PAID, update transaction
        $confirm_params = [
            'gateway'        => $this->getPaymentGatewayName(),
            'amount'         => floatval($transaction['t_amount']),
            'currency'       => $transaction['t_currency'],
            'transaction_id' => $transaction['t_transaction_id'],
            'gateway_transaction_id' => '',
        ];
        $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'success');
        $response = $this->paymentService->confirm($transaction, $confirm_params);
        if ($response['is_success'] == 'ok') {
            return [
                'status' => 'success',
            ];
        }
    }

    private function returnFail() {
        return response('fail', 400);
    }

    private function returnSuccess() {
        return response('', 200);
    }

}
