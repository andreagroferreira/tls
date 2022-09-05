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

class CybersourcePaymentGateway implements PaymentGatewayInterface
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
        return 'cybersource';
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
        $app_env  = $this->isSandBox();
        $orderid  = $translations_data['t_transaction_id'] ?? '';
        $amount   = $translations_data['t_amount'];
        $client   = $translations_data['t_client'];
        $issuer   = $translations_data['t_issuer'];
        $t_service = $translations_data['t_service'] ?? 'tls';
        $cybersource_config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $t_service);
        $currency           = $translations_data['t_currency'] ?? $cybersource_config['common']['currency'];
        $is_live            = $cybersource_config['common']['env'] == 'live' ? true : false;
        if ($is_live && !$app_env) {
            // Live account
            $init_hosturl     = $cybersource_config['prod']['host'];
            $access_key       = $cybersource_config['prod']['access_key'];
            $profile_id       = $cybersource_config['prod']['profile_id'];
            $transaction_type = $cybersource_config['prod']['transaction_type'];
            $secretKey        = $cybersource_config['prod']['secret_key'];
        } else {
            // Test account
            $init_hosturl     = $cybersource_config['sandbox']['host'];
            $access_key       = $cybersource_config['sandbox']['access_key'];
            $profile_id       = $cybersource_config['sandbox']['profile_id'];
            $transaction_type = $cybersource_config['sandbox']['transaction_type'];
            $secretKey        = $cybersource_config['sandbox']['secret_key'];
        }
        $form_fields = [
            'access_key'           => $access_key,
            'profile_id'           => $profile_id,
            'transaction_uuid'     => uniqid(),
            'signed_field_names'   => 'access_key,profile_id,transaction_uuid,signed_field_names,unsigned_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency',
            'unsigned_field_names' => '',
            'signed_date_time'     => gmdate("Y-m-d\TH:i:s\Z"),
            'locale'               => 'en',
            'transaction_type'     => $transaction_type,
            'reference_number'     => $orderid,
            'amount'               => $amount,
            'currency'             => $currency
        ];
        // signature
        $dataToSign       = [];
        $signedFieldNames = explode(",",$form_fields["signed_field_names"]);
        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field . "=" . $form_fields[$field];
        }
        $form_fields['signature'] = base64_encode(hash_hmac('sha256', implode(',', $dataToSign), $secretKey, true));
        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $translations_data);
        return [
            'form_method' => 'post',
            'form_action' => $init_hosturl,
            'form_fields' => $form_fields
        ];
    }

    public function return($return_params)
    {
        $order_id    = $return_params['order_id'];
        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $order_id, 't_tech_deleted' => false]);
        $internet_online_payment_result = array(
            'is_success' => 'ok',
            'orderid'    => $transaction['t_transaction_id'],
            'issuer'     => $transaction['t_issuer'],
            'amount'     => $transaction['t_amount'],
            'message'    => 'Request was processed successfully.',
            'href'       => $transaction['t_redirect_url']
        );
        return $internet_online_payment_result;
    }

    public function notify($notify_params)
    {
        $app_env            = $this->isSandBox();
        $order_id           = $notify_params['req_reference_number'];
        $transaction        = $this->transactionService->fetchTransaction(['t_transaction_id' => $order_id, 't_tech_deleted' => false]);
        $client             = $transaction['t_client'];
        $issuer             = $transaction['t_issuer'];
        $t_service          = $transaction['t_service'] ?? 'tls';
        $cybersource_config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $t_service);
        $is_live            = $cybersource_config['common']['env'] == 'live' ? true : false;
        if ($is_live && !$app_env) {
            // Live account
            $access_key       = $cybersource_config['prod']['access_key'];
            $profile_id       = $cybersource_config['prod']['profile_id'];
            $transaction_type = $cybersource_config['prod']['transaction_type'];
            $secretKey        = $cybersource_config['prod']['secret_key'];
        } else {
            // Test account
            $access_key       = $cybersource_config['sandbox']['access_key'];
            $profile_id       = $cybersource_config['sandbox']['profile_id'];
            $transaction_type = $cybersource_config['sandbox']['transaction_type'];
            $secretKey        = $cybersource_config['sandbox']['secret_key'];
        }
        // signature
        $notify_params['req_access_key']       = $access_key;
        $notify_params['req_profile_id']       = $profile_id;
        $notify_params['req_transaction_type'] = $transaction_type;
        $notify_params['req_reference_number'] = $order_id;
        $notify_params['req_amount']           = $transaction['t_amount'];
        $notify_params['req_currency']         = $transaction['t_currency'] ?? $cybersource_config['common']['currency'];
        $dataToSign       = [];
        $signedFieldNames = explode(",",$notify_params["signed_field_names"]);
        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field . "=" . $notify_params[$field];
        }
        $signature = base64_encode(hash_hmac('sha256', implode(',', $dataToSign), $secretKey, true));
        // 验证数字签名
        if($notify_params['signature'] != $signature){
            Log::warning("ONLINE PAYMENT, Cybersource: digital signature check failed : ". json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return "APPROVED";
        }

        // 交易成功 修改数据库
        $confirm_params = [
            'gateway'        => $this->getPaymentGatewayName(),
            'amount'         => $notify_params['req_amount'],
            'currency'       => $notify_params['req_currency'],
            'transaction_id' => $order_id,
            'gateway_transaction_id' => $notify_params['transaction_id'],
        ];
        $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $notify_params,'success');
        $response = $this->paymentService->confirm($transaction, $confirm_params);
        if($response['is_success'] != 'ok') {
            exit;
        }
        //核对支付授权状态
        if ($notify_params['reason_code'] == 100) {
            return "OK";
        } else {
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $notify_params,'fail');
            Log::warning("ONLINE PAYMENT, Cybersource: Payment authorization check failed : ". json_encode($notify_params, JSON_UNESCAPED_UNICODE));
            return "ERROR";
        }
    }
}