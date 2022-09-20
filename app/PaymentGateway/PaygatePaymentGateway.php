<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\FormGroupService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\PaymentInitiateService;
use App\Services\TransactionLogsService;
use App\Services\TransactionService;
use App\Services\TransactionItemsService;
use App\Services\ApiService;
use Illuminate\Support\Facades\Log;

class PaygatePaymentGateway implements PaymentGatewayInterface
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
    ) {
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
        return 'paygate';
    }

    public function isSandBox()
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    public function checkout()
    {

    }

    public function notify($return_params)
    {
        $error_msg          = array();
        $paygate_id         = $return_params['PAYGATE_ID'] ?? '';
        $pay_request_id     = $return_params['PAY_REQUEST_ID'] ?? '';
        $reference          = $return_params['REFERENCE'] ?? '';
        $return_checksum    = $return_params['CHECKSUM'] ?? '';
        $transaction_status = $return_params['TRANSACTION_STATUS'] ?? '';
        $transaction        = $this->transactionService->fetchTransaction(['t_gateway_transaction_id' => $pay_request_id, 't_tech_deleted' => false]);
        $paygate_config     = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName(), $transaction['t_xref_pa_id']);
        $is_live            = $paygate_config['common']['env'] == 'live' ? true : false;
        $app_env            = $this->isSandBox();
        if ($this->gatewayService->getClientUseFile()) {
            $query_host      = $paygate_config['config']['query_host'] ?? $paygate_config['config']['sandbox_query_host'] ?? '';
            $encryptionKey   = $paygate_config['config']['encryption_key'] ?? $paygate_config['config']['sandbox_encryption_key'] ?? '';
        } else if ($is_live && !$app_env) {
            // Live account
            $query_host      = $paygate_config['prod']['query_host'];
            $encryptionKey   = $paygate_config['prod']['encryption_key'];
        } else {
            // Test account
            $query_host      = $paygate_config['sandbox']['sandbox_query_host'];
            $encryptionKey   = $paygate_config['sandbox']['sandbox_encryption_key'];
        }
        $params = array(
            'PAYGATE_ID'     => $paygate_id,
            'PAY_REQUEST_ID' => $pay_request_id,
            'REFERENCE'      => $reference
        );
        $checksum = md5(implode('', $params) . $encryptionKey);
        $params['CHECKSUM'] = $checksum;
        $result = $this->paymentInitiateService->paymentInitiate('post', $query_host, http_build_query($params));
        $hash = "";
        foreach (explode('&', $result) as $key => $val) {
            $item = explode('=', $val);
            if ($item[0] != "CHECKSUM") continue;
            $hash = $item[1];
        }
        // 验证数字签名
        if($return_checksum != $hash){
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $return_params,'fail');
            Log::warning("ONLINE PAYMENT, PAYGATE: digital signature check failed : ". json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return "APPROVED";
        }

        // 交易成功 修改数据库
        $confirm_params = [
            'gateway'        => $this->getPaymentGatewayName(),
            'amount'         => intval($return_params['AMOUNT'])/100,
            'currency'       => $return_params['CURRENCY'],
            'transaction_id' => $transaction['t_transaction_id'],
            'gateway_transaction_id' => $pay_request_id,
        ];
        $response = $this->paymentService->confirm($transaction, $confirm_params);
        if($response['is_success'] != 'ok') {
            exit;
        }
        //核对支付授权状态
        if ($transaction_status == 1) {
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $return_params,'success');
            return "OK";
        } else {
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $return_params,'fail');
            return "ONLINE PAYMENT, PAYGATE: Payment authorization check failed : ". json_encode($return_params, JSON_UNESCAPED_UNICODE);
        }
    }

    public function redirto($params)
    {
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
        $client  = $translationsData['t_client'];
        $issuer  = $translationsData['t_issuer'];
        $fg_id   = $translationsData['t_xref_fg_id'];
        $orderId = $translationsData['t_transaction_id'] ?? '';
        $application = $this->formGroupService->fetch($fg_id, $client);
        $u_email     = $application['u_relative_email'] ?? $application['u_email'] ?? "tlspay-{$client}-{$fg_id}@tlscontact.com";
        $paygate_config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $pa_id);
        $is_live        = $paygate_config['common']['env'] == 'live' ? true : false;
        $app_env = $this->isSandBox();
        if ($this->gatewayService->getClientUseFile()) {
            $init_hosturl        = $paygate_config['config']['initiate_host'] ?? $paygate_config['config']['sandbox_initiate_host'] ?? '';
            $process_hosturl     = $paygate_config['config']['process_host'] ?? $paygate_config['config']['sandbox_process_host'] ?? '';
            $paygate_id          = $paygate_config['config']['paygate_id'] ?? $paygate_config['config']['sandbox_paygate_id'] ?? '';
            $encryptionKey       = $paygate_config['config']['encryption_key'] ?? $paygate_config['config']['sandbox_encryption_key'] ?? '';
            $pay_method          = 'BT';
        } else if ($is_live && !$app_env) {
            // Live account
            $init_hosturl        = $paygate_config['prod']['initiate_host'];
            $process_hosturl     = $paygate_config['prod']['process_host'];
            $paygate_id          = $paygate_config['prod']['paygate_id'];
            $encryptionKey       = $paygate_config['prod']['encryption_key'];
            $pay_method          = 'BT';
        } else {
            // Test account
            $init_hosturl        = $paygate_config['sandbox']['sandbox_initiate_host'];
            $process_hosturl     = $paygate_config['sandbox']['sandbox_process_host'];
            $paygate_id          = $paygate_config['sandbox']['sandbox_paygate_id'];
            $encryptionKey       = $paygate_config['sandbox']['sandbox_encryption_key'];
            $pay_method          = '';
        }
        $country    = $paygate_config['common']['country'];
        $notify_url = get_callback_url($paygate_config['common']['notify_url']);
        $return_url = get_callback_url($paygate_config['common']['return_url']);
        $params = array(
            'PAYGATE_ID'        => $paygate_id,
            'REFERENCE'         => $orderId,
            'AMOUNT'            => $translationsData['t_amount']*100,
            'CURRENCY'          => $translationsData['t_currency'],
            'RETURN_URL'        => $return_url,
            'TRANSACTION_DATE'  => date('Y-m-d H:i:s'),
            'LOCALE'            => 'en',
            'COUNTRY'           => $country,
            'EMAIL'             => $u_email,
            'PAY_METHOD'        => $pay_method,
            'NOTIFY_URL'        => $notify_url
        );
        $checksum  =  md5(implode('', $params) . $encryptionKey);
        $params['CHECKSUM'] = $checksum;
        $result = $this->paymentInitiateService->paymentInitiate('post', $init_hosturl, http_build_query($params));
        if (strpos($result,'ERROR') !== false) { return [ 'status' => 'fail', 'content' => $result ]; }
        $results = array();
        foreach (explode('&', $result) as $key => $val) {
            $item = explode('=', $val);
            $results[$item[0]] = $item[1];
        }
        unset($results['CHECKSUM']);
        $results['CHECKSUM'] = md5(implode('', $results) . $encryptionKey);
        unset($results['PAYGATE_ID']);
        unset($results['REFERENCE']);
        if (!empty($results['PAY_REQUEST_ID'])) {
            $this->transactionService->update(['t_transaction_id' => $orderId], ['t_gateway_transaction_id' => $results['PAY_REQUEST_ID'], 't_gateway' => $this->getPaymentGatewayName()]);
        }

        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $translationsData);

        return [
            'form_method' => 'post',
            'form_action' => $process_hosturl,
            'form_fields' => $results,
        ];
    }

    public function return($return_params)
    {
        $status = "";
        $pay_request_id     = $return_params['PAY_REQUEST_ID'];
        $transaction_status = $return_params['TRANSACTION_STATUS'];
        $transaction        = $this->transactionService->fetchTransaction(['t_gateway_transaction_id' => $pay_request_id, 't_tech_deleted' => false]);
        switch ($transaction_status) {
            case 0:
                $status = "Not Done";
                break;
            case 1:
                $status = "Approved";
                break;
            case 2:
                $status = "Declined";
                break;
            case 3:
                $status = "Cancelled";
                break;
            case 4:
                $status = "User Cancelled";
                break;
            case 5:
                $status = "Received by PayGate";
                break;
            case 7:
                $status = "Settlement Voided";
                break;
            default;
        }
        $internet_online_payment_result = array(
            'is_success' => $transaction_status !=1 ? 'error' : 'ok',
            'orderid'    => $transaction['t_transaction_id'],
            'issuer'     => $transaction['t_issuer'],
            'amount'     => $transaction['t_amount'],
            'message'    => $status,
            'href'       => $transaction['t_redirect_url']
        );
        return $internet_online_payment_result;
    }
}
