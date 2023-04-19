<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\ApiService;
use App\Services\FormGroupService;
use App\Services\GatewayService;
use App\Services\PaymentInitiateService;
use App\Services\PaymentService;
use App\Services\TransactionItemsService;
use App\Services\TransactionLogsService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;

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

    public function redirto($params)
    {
        $t_id = $params['t_id'];
        $pa_id = $params['pa_id'] ?? null;
        $translations_data = $this->transactionService->getTransaction($t_id);
        if (blank($translations_data)) {
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: transaction not found',
            ];
        }
        if ($pa_id) {
            $this->transactionService->updateById($t_id, ['t_xref_pa_id' => $pa_id]);
        }
        $orderid = $translations_data['t_transaction_id'] ?? '';
        $amount = $translations_data['t_amount'];
        $client = $translations_data['t_client'];
        $issuer = $translations_data['t_issuer'];
        $cybersource_config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $pa_id);
        $currency = $translations_data['t_currency'] ?? $cybersource_config['common']['currency'];
        $is_live = $cybersource_config['common']['env'] == 'live' ? true : false;
        $app_env = $this->isSandBox();
        if (!$this->gatewayService->getClientUseFile()) {
            $init_hosturl = $cybersource_config['config']['host'];
            $access_key = $cybersource_config['config']['access_key'];
            $profile_id = $cybersource_config['config']['profile_id'];
            $transaction_type = $cybersource_config['config']['transaction_type'];
            $secretKey = $cybersource_config['config']['secret_key'];
        } elseif ($is_live && !$app_env) {
            // Live account
            $init_hosturl = $cybersource_config['production']['host'];
            $access_key = $cybersource_config['production']['access_key'];
            $profile_id = $cybersource_config['production']['profile_id'];
            $transaction_type = $cybersource_config['production']['transaction_type'];
            $secretKey = $cybersource_config['production']['secret_key'];
        } else {
            // Test account
            $init_hosturl = $cybersource_config['sandbox']['host'];
            $access_key = $cybersource_config['sandbox']['access_key'];
            $profile_id = $cybersource_config['sandbox']['profile_id'];
            $transaction_type = $cybersource_config['sandbox']['transaction_type'];
            $secretKey = $cybersource_config['sandbox']['secret_key'];
        }
        $form_fields = [
            'access_key' => $access_key,
            'profile_id' => $profile_id,
            'transaction_uuid' => uniqid(),
            'consumer_id' => $translations_data['t_xref_fg_id'],
            'signed_field_names' => 'access_key,profile_id,transaction_uuid,consumer_id,signed_field_names,unsigned_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency',
            'unsigned_field_names' => '',
            'signed_date_time' => gmdate('Y-m-d\\TH:i:s\\Z'),
            'locale' => 'en',
            'transaction_type' => $transaction_type,
            'reference_number' => $orderid,
            'amount' => $amount,
            'currency' => $currency,
        ];
        // signature
        $dataToSign = [];
        $signedFieldNames = explode(',', $form_fields['signed_field_names']);
        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field . '=' . $form_fields[$field];
        }
        $form_fields['signature'] = base64_encode(hash_hmac('sha256', implode(',', $dataToSign), $secretKey, true));
        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $translations_data);

        return [
            'form_method' => 'post',
            'form_action' => $init_hosturl,
            'form_fields' => $form_fields,
        ];
    }

    public function return($return_params)
    {
        $order_id = $return_params['order_id'];
        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $order_id, 't_tech_deleted' => false]);

        if ($transaction['t_status'] === 'closed') {
            $transactionLog = $this->transactionLogsService->fetchByTransactionId($order_id);
            $message = str_replace(
                $this->getPaymentGatewayName() . ' postback:',
                '',
                $transactionLog->tl_content ?? ''
            );

            return [
                'is_success' => 'fail',
                'orderid' => $transaction['t_transaction_id'],
                'issuer' => $transaction['t_issuer'],
                'amount' => $transaction['t_amount'],
                'message' => json_decode($message, true)['error_msg'] ?? 'Payment failed.',
                'href' => $transaction['t_onerror_url'],
            ];
        }
        if ($transaction['t_status'] === 'pending') {
            return [
                'is_success' => 'fail',
                'orderid' => $transaction['t_transaction_id'],
                'issuer' => $transaction['t_issuer'],
                'amount' => $transaction['t_amount'],
                'message' => 'Payment is being processed, please wait.',
                'href' => $transaction['t_redirect_url'],
            ];
        }

        return [
            'is_success' => 'ok',
            'orderid' => $transaction['t_transaction_id'],
            'issuer' => $transaction['t_issuer'],
            'amount' => $transaction['t_amount'],
            'message' => 'Payment done.',
            'href' => $transaction['t_redirect_url'],
        ];
    }

    public function notify($notify_params)
    {
        $order_id = $notify_params['req_reference_number'];
        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $order_id, 't_tech_deleted' => false]);

        if (!isset($notify_params['reason_code'])) {
            Log::warning('ONLINE PAYMENT, Cybersource: Payment validation check failed : ' . json_encode($notify_params, JSON_UNESCAPED_UNICODE));
            $this->paymentService->saveTransactionLog(
                $order_id,
                ['error_msg' => 'Transaction Cancelled'],
                $this->getPaymentGatewayName(),
            );

            return [
                'is_success' => 'fail',
                'message' => $notify_params['message'],
                'href' => $transaction['t_onerror_url'],
            ];
        }

        if ($notify_params['reason_code'] != 100) {
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(), $transaction, $notify_params, 'fail');
            Log::warning('ONLINE PAYMENT, Cybersource: Payment authorization check failed : ' . json_encode($notify_params, JSON_UNESCAPED_UNICODE));
            $this->transactionService->updateById($transaction['t_id'], ['t_status' => 'closed']);

            $this->paymentService->saveTransactionLog(
                $order_id,
                ['error_msg' => $notify_params['message']],
                $this->getPaymentGatewayName(),
            );

            return [
                'is_success' => 'fail',
                'message' => $notify_params['message'],
                'href' => $transaction['t_onerror_url'],
            ];
        }

        $cybersource_config = $this->gatewayService->getGateway(
            $transaction['t_client'],
            $transaction['t_issuer'],
            $this->getPaymentGatewayName(),
            $transaction['t_xref_pa_id']
        );

        $is_live = $cybersource_config['common']['env'] == 'live' ? true : false;
        $app_env = $this->isSandBox();
        if (!$this->gatewayService->getClientUseFile()) {
            $access_key = $cybersource_config['config']['access_key'];
            $profile_id = $cybersource_config['config']['profile_id'];
            $transaction_type = $cybersource_config['config']['transaction_type'];
            $secretKey = $cybersource_config['config']['secret_key'];
        } elseif ($is_live && !$app_env) {
            // Live account
            $access_key = $cybersource_config['production']['access_key'];
            $profile_id = $cybersource_config['production']['profile_id'];
            $transaction_type = $cybersource_config['production']['transaction_type'];
            $secretKey = $cybersource_config['production']['secret_key'];
        } else {
            // Test account
            $access_key = $cybersource_config['sandbox']['access_key'];
            $profile_id = $cybersource_config['sandbox']['profile_id'];
            $transaction_type = $cybersource_config['sandbox']['transaction_type'];
            $secretKey = $cybersource_config['sandbox']['secret_key'];
        }

        $notify_params['req_access_key'] = $access_key;
        $notify_params['req_profile_id'] = $profile_id;
        $notify_params['req_transaction_type'] = $transaction_type;
        $notify_params['req_reference_number'] = $order_id;
        $notify_params['req_amount'] = $transaction['t_amount'];
        $notify_params['req_currency'] = $transaction['t_currency'] ?? $cybersource_config['common']['currency'];
        $dataToSign = [];
        $signedFieldNames = explode(',', $notify_params['signed_field_names']);
        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field . '=' . $notify_params[$field];
        }
        $signature = base64_encode(hash_hmac('sha256', implode(',', $dataToSign), $secretKey, true));

        if ($notify_params['signature'] != $signature) {
            $this->transactionService->updateById($transaction['t_id'], ['t_status' => 'closed']);
            Log::warning('ONLINE PAYMENT, Cybersource: digital signature check failed : ' . json_encode($_POST, JSON_UNESCAPED_UNICODE));

            return [
                'is_success' => 'fail',
                'message' => 'Signature validation failed',
                'href' => $transaction['t_onerror_url'],
            ];
        }

        $confirm_params = [
            'gateway' => $this->getPaymentGatewayName(),
            'amount' => $notify_params['req_amount'],
            'currency' => $notify_params['req_currency'],
            'transaction_id' => $order_id,
            'gateway_transaction_id' => $order_id,
        ];

        $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(), $transaction, $notify_params, 'success');

        return $this->paymentService->confirm($transaction, $confirm_params);
    }
}
