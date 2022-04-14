<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\TransactionLogsService;
use App\Services\TransactionService;
use App\Services\ApiService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;

class GlobalirisPaymentGateway implements PaymentGatewayInterface
{
    private $paymentService;
    private $transactionLogsService;
    private $transactionService;
    private $apiService;

    public function __construct(
        TransactionService $transactionService,
        TransactionLogsService $transactionLogsService,
        PaymentService $paymentService,
        ApiService $apiService
    ){
        $this->transactionService = $transactionService;
        $this->transactionLogsService = $transactionLogsService;
        $this->paymentService = $paymentService;
        $this->apiService         = $apiService;
    }

    public function getPaymentGatewayName() {
        return 'globaliris';
    }

    public function isSandBox()
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    public function checkout() {
        return 'checkout';
    }

    public function notify($params) {
        return 'notify';
    }

    public function redirto($t_id) {
        $translationsData = $this->transactionService->getTransaction($t_id);
        $client = $translationsData['t_client'];
        $issuer = $translationsData['t_issuer'];
        $fg_id = $translationsData['t_xref_fg_id'];
        $config = $this->getConfig($client, $issuer);
        $onlinePayment = $config ? $config['globaliris'] : [];
        $orderId = $translationsData['t_transaction_id'] ?? '';
        $app_env = $this->isSandBox();
        $amount = $translationsData['t_amount'];
        $minFractionDigits = $onlinePayment['common']['min_fraction_digits'];
        if ($minFractionDigits) {
            while ($minFractionDigits > 0) {
                $amount *= 10;
                $minFractionDigits--;
            }
        }
        $amount =  round($amount, 0);
        $applicationsResponse = $this->apiService->callTlsApi('GET', '/tls/v2/' . $client . '/forms_in_group/' . $fg_id);
        $applications = $applicationsResponse['status'] == 200 ? $applicationsResponse['body'] : [];
        $cai_list_with_avs = array_column($applications, 'f_cai');
        $is_live = $onlinePayment['common']['env'] == 'live' ? true : false;
        if ($is_live && !$app_env) {
            // Live account
            $hosturl        = $onlinePayment['prod']['host'] ?? '';
            $merchantid     = $onlinePayment['prod']['merchant_id'] ?? '';
            $account        = $onlinePayment['prod']['account'] ?? '';
            $secret         = $onlinePayment['prod']['secret'] ?? '';
        } else {
            // Test account
            $hosturl        = $onlinePayment['sandbox']['sandbox_host'] ?? '';
            $merchantid     = $onlinePayment['sandbox']['sandbox_merchant_id'] ?? '';
            $account        = $onlinePayment['sandbox']['sandbox_account'] ?? '';
            $secret         = $onlinePayment['sandbox']['sandbox_secret'] ?? '';
        }
        $curr           = $translationsData['t_currency'] ?? '';
        $txn_fee_extra  = $onlinePayment['common']['txn_fee_extra'] ?? '';
        $txn_fee_rate   = $onlinePayment['common']['txn_fee_rate'] ?? '';
        $returnurl      = $onlinePayment['common']['return_url'] ?? '';
        $timestamp      = strftime("%Y%m%d%H%M%S");

        $tmp = "$timestamp.$merchantid.$orderId.$amount.$curr";
        $sha1hash = sha1($tmp);
        $tmp = "$sha1hash.$secret";
        $sha1hash = sha1($tmp);
        $cai = implode(', ', array_unique($cai_list_with_avs));
        $url = url('/');
        $params = [
            'MERCHANT_ID' => $merchantid,
            'ACCOUNT' => $account,
            'ORDER_ID' => $orderId,
            'CURRENCY' => $curr,
            'AMOUNT' => $amount,
            'TIMESTAMP' => $timestamp,
            'SHA1HASH' => $sha1hash,
            'MERCHANT_RESPONSE_URL' => get_callback_url($returnurl),
            'AUTO_SETTLE_FLAG' => '1',
            'TLS_CURRENCY' => $curr,
            'VAR_REF' => $cai,
            'COMMENT1' => $cai,
            'COMMENT2' => $url
        ];
        return [
            'form_method' => 'post',
            'form_action' => $hosturl,
            'form_fields' => $params,
        ];
    }

    public function return($params) {
        $timestamp  = $params['TIMESTAMP'] ?? '';
        $result     = $params['RESULT'] ?? '';
        $orderId    = $params['ORDER_ID'] ?? '';
        $message    = $params['MESSAGE'] ?? '';
        $authcode   = $params['AUTHCODE'] ?? '';
        $pasref     = $params['PASREF'] ?? '';
        $realexsha1 = $params['SHA1HASH'] ?? '';

        $translationsData =  $this->transactionService->fetchTransaction(['t_transaction_id' => $orderId]);
        $client = $translationsData['t_client'];
        $issuer = $translationsData['t_issuer'];
        if (empty($translationsData)) {
            Log::warning("ONLINE PAYMENT,". strtoupper($this->getPaymentGatewayName()) . " : No transaction found in the database for " . $orderId . "\n" .
                json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return [
                'is_success' => 'error',
                'orderid' => $orderId,
                'issuer' => $issuer,
                'href' => $translationsData['t_redirect_url'],
                'message' => 'transaction_id_not_exists'
            ];
        }
        $received_amount   = $params['AMOUNT'] ?? '';
        $config = $this->getConfig($client, $issuer);
        $onlinePayment = $config ? $config['globaliris'] : [];
        $app_env = $this->isSandBox();

        $is_live = $onlinePayment['common']['env'] == 'live' ? true : false;
        if ($is_live && !$app_env) {
            // Live account
            $merchantid     = $onlinePayment['prod']['merchant_id'] ?? '';
            $secret         = $onlinePayment['prod']['secret'] ?? '';
        } else {
            // Test account
            $merchantid     = $onlinePayment['sandbox']['sandbox_merchant_id'] ?? '';
            $secret         = $onlinePayment['sandbox']['sandbox_secret'] ?? '';
        }
        $tmp = "$timestamp.$merchantid.$orderId.$result.$message.$pasref.$authcode";
        $sha1hash = sha1($tmp);
        $tmp = "$sha1hash.$secret";
        $sha1hash = sha1($tmp);
        if ($result == "00") {
            $flag = true;
        } else {
            $flag = false;
            $msg = $result;
//            if ($result == "101") {
//                $flag = false;
//                $msg = 'Sorry, the transaction has been declined and was not successful.';
//            } elseif ($result == "103") {
//                $flag = false;
//                $msg = 'Sorry, this card has been reported lost or stolen, please contact your bank.';
//            } elseif ($result == "205") {
//                $flag = false;
//                $msg = 'Sorry, there has been a communications error, please try again later.';
//            } else {
//                $flag = false;
//                $msg = 'Unknown error, please try again.';
//            }
        }
        if ($sha1hash != $realexsha1) {
            $flag = false;
            $msg = 'signature_verification_failed';
        }
        if ($flag) {
            $minFractionDigits = $onlinePayment['common']['min_fraction_digits'];
            if ($minFractionDigits) {
                while ($minFractionDigits > 0) {
                    $received_amount /= 10;
                    $minFractionDigits--;
                }
            }
            $confirm_params = [
                'gateway' => $this->getPaymentGatewayName(),
                'amount' => $received_amount,
                'currency' => $params['TLS_CURRENCY'] ?? '',
                'transaction_id' => $orderId,
                'gateway_transaction_id' => $pasref ?? '',
            ];
            return $this->paymentService->confirm($translationsData, $confirm_params);
        } else {
            $result = [
                'is_success' => 'error',
                'orderid' => $orderId,
                'issuer' => $issuer,
                'message' => $msg,
                'href' => $translationsData['t_redirect_url']
            ];
            $this->transactionLogsService->create(['tl_xref_transaction_id' => $translationsData['t_transaction_id'], 'tl_content' =>json_encode($result)]);
            return $result;
        }
    }

    public function getConfig($client, $issuer)
    {
        $country = substr($issuer, 0, 2);
        $payment_client = substr($issuer, -2);
        $country_level_config = $country . 'All2' . $payment_client;
        $global_config = 'allAll2all';
        $client_payment_gateway = config('payment_gateway')[$client];
        if (!empty($client_payment_gateway[$issuer])) {
            $config = $client_payment_gateway[$issuer];
        } elseif (!empty($client_payment_gateway[$country_level_config])) {
            $config = $client_payment_gateway[$country_level_config];
        } elseif (!empty($client_payment_gateway[$global_config])) {
            $config = $client_payment_gateway[$global_config];
        } else {
            $config = [];
        }
        return $config;

    }
}
