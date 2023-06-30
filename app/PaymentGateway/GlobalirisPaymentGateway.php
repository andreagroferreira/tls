<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\ApiService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionLogsService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;

class GlobalirisPaymentGateway implements PaymentGatewayInterface
{
    private $paymentService;
    private $transactionLogsService;
    private $transactionService;
    private $apiService;
    private $gatewayService;

    public function __construct(
        TransactionService $transactionService,
        TransactionLogsService $transactionLogsService,
        PaymentService $paymentService,
        ApiService $apiService,
        GatewayService $gatewayService
    ) {
        $this->transactionService = $transactionService;
        $this->transactionLogsService = $transactionLogsService;
        $this->paymentService = $paymentService;
        $this->apiService = $apiService;
        $this->gatewayService = $gatewayService;
    }

    public function getPaymentGatewayName()
    {
        return 'globaliris';
    }

    public function isSandBox()
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    public function checkout()
    {
        return 'checkout';
    }

    public function notify($params)
    {
        return 'notify';
    }

    public function redirto($params)
    {
        $t_id = $params['t_id'];
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
        $fg_id = $translationsData['t_xref_fg_id'];
        if ($this->gatewayService->getClientUseFile()) {
            $config = $this->gatewayService->getConfig($client, $issuer);
            $onlinePayment = $config ? $config['globaliris'] : [];
        } else {
            $onlinePayment = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $pa_id);
        }
        $orderId = $translationsData['t_transaction_id'] ?? '';

        $amount = $translationsData['t_amount'];
        $minFractionDigits = $onlinePayment['common']['min_fraction_digits'];
        if ($minFractionDigits) {
            while ($minFractionDigits > 0) {
                $amount *= 10;
                --$minFractionDigits;
            }
        }
        $amount = round($amount, 0);
        $applicationsResponse = $this->apiService->callTlsApi('GET', '/tls/v2/' . $client . '/forms_in_group/' . $fg_id);
        $applications = $applicationsResponse['status'] == 200 ? $applicationsResponse['body'] : [];
        $app_env = $this->isSandBox();

        $cai_list_with_avs = array_column($applications, 'f_cai');
        $is_live = $onlinePayment['common']['env'] == 'live' ? true : false;
        if (!$this->gatewayService->getClientUseFile()) {
            $hosturl = $onlinePayment['config']['host'] ?? '';
            $merchantid = $onlinePayment['config']['merchant_id'] ?? '';
            $account = $onlinePayment['config']['account'] ?? '';
            $secret = $onlinePayment['config']['secret'] ?? '';
        } elseif ($is_live && !$app_env) {
            // Live account
            $hosturl = $onlinePayment['production']['host'] ?? '';
            $merchantid = $onlinePayment['production']['merchant_id'] ?? '';
            $account = $onlinePayment['production']['account'] ?? '';
            $secret = $onlinePayment['production']['secret'] ?? '';
        } else {
            // Test account
            $hosturl = $onlinePayment['sandbox']['sandbox_host'] ?? '';
            $merchantid = $onlinePayment['sandbox']['sandbox_merchant_id'] ?? '';
            $account = $onlinePayment['sandbox']['sandbox_account'] ?? '';
            $secret = $onlinePayment['sandbox']['sandbox_secret'] ?? '';
        }
        $curr = $translationsData['t_currency'] ?? '';
        $txn_fee_extra = $onlinePayment['common']['txn_fee_extra'] ?? '';
        $txn_fee_rate = $onlinePayment['common']['txn_fee_rate'] ?? '';
        $returnurl = $onlinePayment['common']['return_url'] ?? '';
        $timestamp = strftime('%Y%m%d%H%M%S');

        $tmp = "{$timestamp}.{$merchantid}.{$orderId}.{$amount}.{$curr}";
        $sha1hash = sha1($tmp);
        $tmp = "{$sha1hash}.{$secret}";
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
            'COMMENT2' => $url,
            'HPP_CAPTURE_ADDRESS' => true,
            'HPP_REMOVE_SHIPPING' => true,
            'HPP_DO_NOT_RETURN_ADDRESS' => true,
        ];
        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $translationsData);

        return [
            'form_method' => 'post',
            'form_action' => $hosturl,
            'form_fields' => $params,
        ];
    }

    public function return($params)
    {
        $timestamp = $params['TIMESTAMP'] ?? '';
        $result = $params['RESULT'] ?? '';
        $orderId = $params['ORDER_ID'] ?? '';
        $message = $params['MESSAGE'] ?? '';
        $authcode = $params['AUTHCODE'] ?? '';
        $pasref = $params['PASREF'] ?? '';
        $realexsha1 = $params['SHA1HASH'] ?? '';
        $translationsData = $this->transactionService->fetchTransaction(['t_transaction_id' => $orderId]);
        if (empty($translationsData)) {
            Log::warning('ONLINE PAYMENT,' . strtoupper($this->getPaymentGatewayName()) . ' : No transaction found in the database for ' . $orderId . "\n" .
                json_encode($_POST, JSON_UNESCAPED_UNICODE));

            return [
                'is_success' => 'error',
                'orderid' => $orderId,
                'issuer' => '',
                'href' => '',
                'message' => 'transaction_id_not_exists',
            ];
        }
        $client = $translationsData['t_client'];
        $issuer = $translationsData['t_issuer'];
        $received_amount = $params['AMOUNT'] ?? '';
        if ($this->gatewayService->getClientUseFile()) {
            $config = $this->gatewayService->getConfig($client, $issuer);
            $onlinePayment = $config ? $config['globaliris'] : [];
        } else {
            $onlinePayment = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $translationsData['t_xref_pa_id']);
        }

        $app_env = $this->isSandBox();
        $is_live = $onlinePayment['common']['env'] == 'live' ? true : false;
        if (!$this->gatewayService->getClientUseFile()) {
            $merchantid = $onlinePayment['config']['merchant_id'] ?? '';
            $secret = $onlinePayment['config']['secret'] ?? '';
            $subaccount = $onlinePayment['config']['account'] ?? '';
        } elseif ($is_live && !$app_env) {
            // Live account
            $merchantid = $onlinePayment['production']['merchant_id'] ?? '';
            $secret = $onlinePayment['production']['secret'] ?? '';
            $subaccount = $onlinePayment['production']['account'] ?? '';
        } else {
            // Test account
            $merchantid = $onlinePayment['sandbox']['sandbox_merchant_id'] ?? '';
            $secret = $onlinePayment['sandbox']['sandbox_secret'] ?? '';
            $subaccount = $onlinePayment['sandbox']['sandbox_account'] ?? '';
        }

        $tmp = "{$timestamp}.{$merchantid}.{$orderId}.{$result}.{$message}.{$pasref}.{$authcode}";
        $sha1hash = sha1($tmp);
        $tmp = "{$sha1hash}.{$secret}";
        $sha1hash = sha1($tmp);
        if ($result == '00') {
            $flag = true;
        } else {
            $flag = false;
            $msg = $result;
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
                    --$minFractionDigits;
                }
            }

            $confirm_params = [
                'gateway' => $this->getPaymentGatewayName(),
                'amount' => $received_amount,
                'currency' => $params['TLS_CURRENCY'] ?? '',
                'transaction_id' => $orderId,
                'gateway_transaction_id' => $pasref ?? '',
                't_gateway_account' => $merchantid ?? '',
                't_gateway_subaccount' => $subaccount ?? '',
            ];
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(), $translationsData, $params, 'success');

            return $this->paymentService->confirm($translationsData, $confirm_params);
        }
        $result = [
            'is_success' => 'error',
            'orderid' => $orderId,
            'issuer' => $issuer,
            'message' => $msg,
            'gatewayMessage' => $message,
            'href' => $translationsData['t_onerror_url'],
        ];
        $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(), $translationsData, $params, 'fail');
        $this->transactionLogsService->create(['tl_xref_transaction_id' => $translationsData['t_transaction_id'], 'tl_content' => json_encode($result)]);

        return $result;
    }
}
