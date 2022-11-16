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
        PaymentInitiateService  $paymentInitiateService,
        TransactionService      $transactionService,
        TransactionLogsService  $transactionLogsService,
        TransactionItemsService $transactionItemsService,
        FormGroupService        $formGroupService,
        GatewayService          $gatewayService,
        PaymentService          $paymentService,
        ApiService              $apiService
    )
    {
        $this->paymentInitiateService = $paymentInitiateService;
        $this->transactionService = $transactionService;
        $this->transactionLogsService = $transactionLogsService;
        $this->transactionItemsService = $transactionItemsService;
        $this->formGroupService = $formGroupService;
        $this->gatewayService = $gatewayService;
        $this->paymentService = $paymentService;
        $this->apiService = $apiService;
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

    public function redirto($params)
    {
        $t_id = $params['t_id'];
        $pa_id = $params['pa_id'] ?? null;
        $transaction_data = $this->transactionService->getTransaction($t_id);
        if (blank($transaction_data)) {
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: transaction not found'
            ];
        } else if ($pa_id) {
            $this->transactionService->updateById($t_id, ['t_xref_pa_id' => $pa_id]);
        }
        $orderid = $transaction_data['t_transaction_id'] ?? '';
        $amount = $transaction_data['t_amount'];
        $client = $transaction_data['t_client'];
        $issuer = $transaction_data['t_issuer'];
        //$fg_id   = $transaction_data['t_xref_fg_id'];
        $payfort_config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $pa_id);
        $pay_config = $this->getPaySecret($payfort_config);
        $amount = str_replace(',', '', $amount);
        $currency = $transaction_data['t_currency'] ?? $payfort_config['common']['currency'];
        $store_id = $pay_config['store_id'];
        $store_private_key = $pay_config['store_private_key'];
        $host = $pay_config['host'];
        $expirationConfig = $payfort_config['common']['expiration'] ?? '+2 hours';
        $expirationDate = gmdate('Y-m-d\TH:i:s', strtotime($expirationConfig)).'GMT';

        $code = $transaction_data['t_gateway_transaction_id'] ?? '';
        if ($code) {
            $response = $this->apiService->callGeneralApi('get', $host . '/' . $code, '', $this->getHeaders($pay_config));
            Log::info('Binga redirto $response query code:' . json_encode($response));
        } else {
            $hash_sign = md5("PRE-PAY" . $amount . $store_id . $orderid . "TLS Contact" . $store_private_key);
            $params = [
                "applicantId" => $orderid,
                "amount" => $amount,
                "expirationDate" => $expirationDate,
                "storeId" => $store_id,
                "bookedFor" => "",
                "country" => $client,
                "payUrl" => get_callback_url($payfort_config['common']['notify_url']),
                "checksum" => $hash_sign
            ];
            $response = $this->apiService->callGeneralApiJson('POST', $host . '/prepayTls', $params, $this->getHeaders($pay_config));
            Log::info('Binga redirto $response:' . json_encode($response));
            $this->paymentService->saveTransactionLog($transaction_data['t_transaction_id'], $response, $this->getPaymentGatewayName());
        }
        $order = $response['body']['orders']['order'];
        if ((array_get($response, 'status') != 200 && array_get($response, 'status') != 201) || blank($order['code'])) {
            $this->logWarning('Create checkout failed.', $params);
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: payment failed.'
            ];
        }
        if ($transaction_data['t_status'] == 'pending') {
            $update_fields = [
                't_gateway_transaction_id' => $order['code'],
            ];
            $this->transactionService->updateById($transaction_data['t_id'], $update_fields);
        }

        $creationDate = str_replace(['T', 'Z'], ' ', $order['creationDate']);
        $expirationDate = gmdate('Y-m-d H:i:s', strtotime($creationDate . $expirationConfig));
        $nowDate = str_replace(['T', 'Z'], ' ', gmdate("Y-m-d\TH:i:s\Z"));
        $minuteDiff = intval((strtotime($expirationDate) - strtotime($nowDate)) / 60);
        $countdown = $minuteDiff > 0 ? $minuteDiff : 0;
        $result = array_get($response, 'body.result');

        $form_fields = [
            'orderId' => $orderid,
            'result' => $result,
            'result_message' => 'Order created successfully',
            'code' => $order['code'],
            'amount' => $order['amount'],
            'currency' => $currency,
            'countdown' => $countdown,
            'return_url' => $transaction_data['t_redirect_url'],
        ];

        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $transaction_data);

        return [
            'form_method' => 'post',
            'form_action' => get_callback_url($payfort_config['common']['cash_url']),
            'form_fields' => $form_fields
        ];
    }

    public function return($return_params)
    {
        return true;
    }

    public function notify($notify_params)
    {
        $order_id = $notify_params['externalId'] ?? '';
        $nowDate = gmdate("Y-m-d\TH:i:s") . 'GMT+00:00';
        Log::info('binga start notify:' . json_encode($notify_params));
        $code = $notify_params['code'];
        $amount = $notify_params['amount'];
        $orderCheckSum = $notify_params['orderCheckSum'];
        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $order_id, 't_tech_deleted' => false]);
        if ($transaction) {
            $this->transactionLogsService->create(['tl_xref_transaction_id' => $transaction['t_transaction_id'], 'tl_content' => json_encode($notify_params)]);
        }
        if (empty($order_id)) {
            $msg = 'empty_externalId';
            return $msg;
        }
        if (empty($transaction)) {
            $msg = 'transaction_id_not_exists';
            return $msg;
        }
        if (strtolower($transaction['t_status']) == 'done') {
            Log::info("binga notify done");
            return '100;' . $nowDate;
        }
        if (strtolower($transaction['t_status']) == 'close') {
            $msg = 'transaction_cancelled';
            return $msg;
        }

        $payfort_config = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName(), $transaction['t_xref_pa_id']);
        $pay_config = $this->getPaySecret($payfort_config);
        $store_id = $pay_config['store_id'];
        $store_private_key = $pay_config['store_private_key'];
        $sign_string = "PAY" . $amount . $store_id . $order_id . "TLS Contact" . $store_private_key;
        $hash_sign = md5($sign_string);
        //var_dump($hash_sign);exit;
        //$orderCheckSum
        if ($orderCheckSum == $hash_sign) {
            ##check amount
            $expected_amount = number_format($transaction['t_amount'], 2);
            $amount_matched = (str_replace(',', '', $expected_amount) == str_replace(',', '', $amount));
            if (!$amount_matched) {
                Log::warning("Binga notify: data check amount failed : ($expected_amount == $amount)");
                $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $notify_params,'fail');
                return '000;' . $nowDate;
            }
            $t_gateway_transaction_id = $transaction['t_gateway_transaction_id'];
            if ($code != $t_gateway_transaction_id) {
                Log::warning("Binga notify: data check code failed : ($t_gateway_transaction_id == $code)");
                $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $notify_params,'fail');
                return '000;' . $nowDate;
            }
            if ($transaction['t_status'] == 'pending') {
                // confirm the elements of the payment
                $confirm_params = [
                    'gateway' => $this->getPaymentGatewayName(),
                    'amount' => floatval($transaction['t_amount']),
                    'currency' => $transaction['t_currency'],
                    'transaction_id' => $transaction['t_transaction_id'],
                    'gateway_transaction_id' => $code,
                ];
                $response = $this->paymentService->confirm($transaction, $confirm_params);
                Log::info('binga notify $response:' . json_encode($response));
                if ($response['is_success'] == 'ok') {
                    Log::info('binga notify payment succeed, status updated！');
                } else {
                    Log::info('binga notify payment succeed, failed to update status！');
                }
                $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $notify_params,'success');
                return '100;' . $nowDate;
            }
        } else {
            Log::warning("binga notify: digital orderCheckSum failed : " . json_encode($notify_params, JSON_UNESCAPED_UNICODE));
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $notify_params,'fail');
            return '000;' . $nowDate;
        }
    }

    private function getPaySecret($pay_config) {
        if ($this->gatewayService->getClientUseFile()) {
            $app_env = $this->isSandBox();
            $is_live = ($pay_config['common']['env'] == 'live');
            $key = ($is_live && !$app_env) ? 'production' : 'sandbox';
        } else {
            $key = 'config';
        }
        return $pay_config[$key];
    }

    protected function getHeaders(array $pay_config)
    {
        $encodedAuth = base64_encode($pay_config['merchant_login'] . ":" . $pay_config['merchant_password']);
        return [
            'Authorization' => 'Basic ' . $encodedAuth,
            'Content-Type' => "application/json",
            'Accept' => "application/json",
        ];
    }

    protected function logWarning($message, $params)
    {
        Log::warning('ONLINE PAYMENT, ' . $this->getPaymentGatewayName() . ' ' . $message);
        Log::warning(json_encode($params, JSON_UNESCAPED_UNICODE));
    }

}
