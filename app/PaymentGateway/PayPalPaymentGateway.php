<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionLogsService;
use App\Services\TransactionService;
use App\Services\ApiService;
use Illuminate\Support\Facades\Log;

class PayPalPaymentGateway implements PaymentGatewayInterface
{
    private $transactionLogsService;
    private $transactionService;
    private $paymentService;
    private $apiService;
    private $gatewayService;


    public function __construct(
        TransactionService $transactionService,
        TransactionLogsService $transactionLogsService,
        PaymentService $paymentService,
        ApiService $apiService,
        GatewayService $gatewayService
    ){
        $this->transactionService = $transactionService;
        $this->transactionLogsService = $transactionLogsService;
        $this->paymentService = $paymentService;
        $this->apiService         = $apiService;
        $this->gatewayService     = $gatewayService;
    }

    public function getPaymentGatewayName() {
        return 'paypal';
    }

    public function isSandBox()
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    public function checkout() {}

    public function notify($params) {

        $orderId = $params['urlData']['transid'];
        if (!$orderId || !$params['urlData']['fg_id']) {
            Log::warning("ONLINE PAYMENT, Paypal: No forms group provided");
            return [
                'status' => 'error',
                'message' => 'ONLINE PAYMENT, Paypal: No forms group provided',
            ];
        }
        $error_msg = array();
        $translationsData =  $this->transactionService->fetchTransaction(['t_transaction_id' => $params['urlData']['transid']]);
        $client = $translationsData['t_client'];
        $issuer = $translationsData['t_issuer'];

        $payfort_config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $translationsData['t_xref_pa_id']);
        $app_env = $this->isSandBox();
        $is_live = $payfort_config['common']['env'] == 'live' ? true : false;
        if ($this->gatewayService->getClientUseFile()) {
            $url = $payfort_config['config']['host'] ?? $payfort_config['config']['sandbox_host'] ?? '';
        } else if ($is_live  && !$app_env) {
            // Live account
            $url = $payfort_config['prod']['host'];
        } else {
            // Test account
            $url = $payfort_config['sandbox']['sandbox_host'];
        }

        $result = $this->paymentNotify($url);
        if ($result['verified']) {
            $payment_status       = $params['formData']['payment_status'];
            //PayPal advised security check #1
            if ($payment_status == 'Completed') {
                if (isset($orderId)) {
                    $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $orderId]);
                    if (!isset($transaction)) {
                        Log::warning("ONLINE PAYMENT, Paypal: No transaction found in the database for " . $orderId);
                        return [
                            'status' => 'error',
                            'message' => 'ONLINE PAYMENT, Paypal: No transaction found in the database for ' . $orderId,
                        ];
                        // it does not make sense to redirect in backend post request, so just exit here
//                        return redirect($transaction['t_redirect_url']);
                    }
                    $confirm_params = [
                        'gateway' => $this->getPaymentGatewayName(),
                        'amount' => $params['formData']['mc_gross'],
                        'currency' => $params['formData']['mc_currency'],
                        'transaction_id' => $orderId,
                        'gateway_transaction_id' => $params['formData']['txn_id'],
                    ];
                    $this->paymentService->confirm($transaction, $confirm_params);
                } else {
                    $error_msg[] = 'Invalid Paypal transaction.';
                }
            } else {
                $error_msg[] =  'The payment could not be completed.';
            }
        } else {
            $error_msg[] = 'Transaction could not be verified.';
        }
        $error = array_merge($error_msg, $result['error']);
        $this->responseLog($params, $error, $orderId);
        if ($error) {
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$translationsData, $params,'fail');
            return [
                'status' => 'error',
                'message' => $error,
            ];
        } else {
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$translationsData, $params,'success');
            return [
                'status' => 'success',
            ];
        }
    }

    public function redirto($params) {
        $t_id = $params['t_id'];
        $pa_id = $params['pa_id'] ?? null;
        $translationsData = $this->transactionService->getTransaction($t_id);
        if (blank($translationsData)) {
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: transaction not found'
            ];
        } else if ($pa_id) {
            $this->transactionService->updateById($t_id, ['t_xref_pa_id' => $pa_id]);
        }
        $client = $translationsData['t_client'];
        $issuer = $translationsData['t_issuer'];
        $fg_id = $translationsData['t_xref_fg_id'];
        $onlinePayment = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $pa_id);
        $orderId = $translationsData['t_transaction_id'] ?? '';
        $amount = $translationsData["t_amount"] ?? '';
        $applicationsResponse = $this->apiService->callTlsApi('GET', '/tls/v2/' . $client . '/forms_in_group/' . $fg_id);
        $applications = $applicationsResponse['status'] == 200 ? $applicationsResponse['body'] : [];
        $cai_list_with_avs = array_column($applications, 'f_cai');
        $is_live = $onlinePayment['common']['env'] == 'live' ? true : false;
        $app_env = $this->isSandBox();
        if ($this->gatewayService->getClientUseFile()) {
            $hosturl        = $onlinePayment['config']['host'] ?? $onlinePayment['config']['sandbox_host'] ?? '';
            $account        = $onlinePayment['config']['account'] ?? $onlinePayment['config']['sandbox_account'] ?? '';
        } else if ($is_live && !$app_env) {
            // Live account
            $hosturl        = $onlinePayment['prod']['host'] ?? '';
            $account        = $onlinePayment['prod']['account'] ?? '';
        } else {
            // Test account
            $hosturl        = $onlinePayment['sandbox']['sandbox_host'] ?? '';
            $account        = $onlinePayment['sandbox']['sandbox_account'] ?? '';
        }
        $curr           = $translationsData['t_currency'] ?? '';
        $txn_fee_extra  = $onlinePayment['common']['txn_fee_extra'] ?? '';
        $txn_fee_rate   = $onlinePayment['common']['txn_fee_rate'] ?? '';
        $returnurl      = get_callback_url(($onlinePayment['common']['return_url'] ?? '') . '?t_id=' . $t_id);

        $ipnurl  = get_callback_url(($onlinePayment['common']['notify_url'] ?? '') . '?fg_id=' . $translationsData['t_xref_fg_id'] . '&transid=' . $orderId);
// Divers data for reporting purpose
        $cai = implode(', ', array_unique($cai_list_with_avs));

        if (isset($translationsData['t_status']) && $translationsData['t_status'] == 'pending') {
            $this->transactionService->updateById($t_id, ['t_status' => 'waiting']);
        }

        $params = [
            'custom' => $orderId,
            'cmd' => "_xclick",
            'business' => $account,
            'currency_code' => $curr,
            'item_name' => $cai,
            'amount' => $amount,
            'quantity' => "1",
            'return' => $returnurl,
            'notify_url' => $ipnurl,
        ];

        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $translationsData);

        return [
            'form_method' => 'post',
            'form_action' => $hosturl,
            'form_fields' => $params,
        ];
    }

    public function return($params) {
        $translationsData = $this->transactionService->getTransaction($params);
        $transaction_status = $translationsData['t_status'] ?? '';
        $link = $translationsData['t_redirect_url'];
        if ($transaction_status != 'done') {
            $link = get_callback_url('paypal/return') . '?t_id=' . $params;
        }
        $result = [
            'is_success' => $transaction_status != 'done' ? 'waiting' : 'ok',
            'orderid' => $translationsData['t_transaction_id'],
            't_id' => $params,
            'href' => $link
        ];
        return $result;
    }

    public function wait($t_id) {
        $translationsData = $this->transactionService->getTransaction($t_id);
        $status = ($translationsData['t_status'] != 'done') ? 'waiting' : 'ok';
        return Response()->json(['status' => $status]);
    }

    private function responseLog($params, $error, $orderId) {
        $sensitive_fields = array(
            'address_street',
            'address_zip',
            'first_name',
            'address_country_code',
            'address_country',
            'address_name',
            'address_city',
            'payer_email',
            'last_name',
            'address_state',
            'residence_country',
        );
        $post_data = $params['formData'];
        foreach ($params as $key => $value) {
            if (in_array($key, $sensitive_fields)) {
                $post_data[$key] = 'xxx';
            }
        }
        $log = [
            'formField' => $post_data,
            'error' => $error
        ];
        $this->transactionLogsService->create(['tl_xref_transaction_id' => $orderId, 'tl_content' =>json_encode($log)]);
    }

    private function paymentNotify($url) {
        $encoded_data = 'cmd=_notify-validate';
        $encoded_data .= '&'.file_get_contents('php://input');
        $ch = curl_init();
        $error_msg = [];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);

        $response = curl_exec($ch);
        $response_status = strval(curl_getinfo($ch, CURLINFO_HTTP_CODE));

        if ($response === false || $response_status == '0') {
            $errno = curl_errno($ch);
            $errstr = curl_error($ch);
            Log::warning("ONLINE PAYMENT, Paypal: cURL error [$errno] $errstr");
        }

        if (strpos($response_status, '200') === false) {
            $error_msg[] = "ONLINE PAYMENT, Paypal: Invalid response status ".$response_status;
        }
        $verified = false;
        if (strpos($response, "VERIFIED") !== false) {
            $verified = true;
        } else if (strpos($response, "INVALID") !== false) {
            $verified = false;
        } else {
            $error_msg[] = "ONLINE PAYMENT, Paypal: Unexpected response";
        }
        return [
            'verified' => $verified,
            'error' => $error_msg
        ];
    }
}
