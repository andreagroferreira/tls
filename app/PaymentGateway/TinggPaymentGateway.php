<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\ApiService;
use App\Services\FormGroupService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TinggPaymentGateway implements PaymentGatewayInterface
{
    private $transactionService;
    private $formGroupService;
    private $gatewayService;
    private $paymentService;
    private $apiService;

    public function __construct(
        TransactionService $transactionService,
        FormGroupService $formGroupService,
        GatewayService $gatewayService,
        PaymentService $paymentService,
        ApiService $apiService
    )
    {
        $this->transactionService = $transactionService;
        $this->formGroupService   = $formGroupService;
        $this->gatewayService     = $gatewayService;
        $this->paymentService     = $paymentService;
        $this->apiService         = $apiService;
    }

    public function getPaymentGatewayName()
    {
        return 'tingg';
    }

    public function isSandBox()
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    public function checkout()
    {
        return true;
    }

    public function notify($params)
    {
        if(empty($params['merchantTransactionID']) || empty($params['serviceCode'])) {
            return [
                'status' => 'error',
                'message' => 'no_data_received'
            ];
        }

        $transaction_id = str_replace('_', '-', $params['merchantTransactionID']);
        $this->paymentService->saveTransactionLog($transaction_id, $params, $this->getPaymentGatewayName());

        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $transaction_id]);
        if (empty($transaction)) {
            Log::warning("ONLINE PAYMENT, TINGG : No transaction found in the database for " . $transaction_id . "\n" .
                json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return [
                'status' => 'error',
                'message' => 'transaction_id_not_exists'
            ];
        }
        $tingg_config = $this->getTinggConfig($transaction, $transaction['t_xref_pa_id']);
        $bearer_token = $this->apiService->getTinggAuthorization($tingg_config);
        $response = $this->apiService->getTinggQueryStatus($params, $bearer_token, $tingg_config);
        if(!empty($response['status']) && $response['status'] == 200) {
            $payment = $response['body']['results'];
            $confirm_params = [
                'gateway' => $this->getPaymentGatewayName(),
                'amount' => $payment['amountPaid'] ?? '',
                'currency' => $payment['paymentCurrencyCode'] ?? 0,
                'transaction_id' => $transaction_id,
                'gateway_transaction_id' => current($payment['payments'])['payerTransactionID'] ?? '',
            ];
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $response,'success');
            $notify_response = $this->paymentService->confirm($transaction, $confirm_params);
            $result = [
                "checkoutRequestID"     => $params['checkoutRequestID'],
                "merchantTransactionID" => $params['merchantTransactionID'],
                "statusDescription"     => $params['requestStatusDescription'],
                "receiptNumber"         => ""
            ];
            if ($notify_response['is_success'] == 'error') {
                Log::warning("ONLINE PAYMENT, TINGG : Data verification failed" . "\n" . json_encode($notify_response, JSON_UNESCAPED_UNICODE));
                $result['statusCode'] = 180;
                return $result;
            }
            $result['statusCode'] = ($params['requestStatusCode'] == 178) ? 183 : $params['requestStatusCode'];
            return $result;
        } else {
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $response,'fail');
            return [
                'status' => 'error',
                'message' => 'transaction_id_not_exists'
            ];
        }
    }

    public function redirto($params)
    {
        $t_id = $params['t_id'];
        $pa_id = $params['pa_id'] ?? null;
        $transaction = $this->transactionService->getTransaction($t_id);
        if (blank($transaction)) {
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: transaction not found'
            ];
        } else if ($pa_id) {
            $this->transactionService->updateById($t_id, ['t_xref_pa_id' => $pa_id]);
        }
        $client       = $transaction['t_client'];
        $issuer       = $transaction['t_issuer'];
        $fg_id        = $transaction['t_xref_fg_id'];
        $tingg_config = $this->getTinggConfig($transaction, $pa_id);
        $application  = $this->formGroupService->fetch($fg_id, $client);
        $u_surname    = $application['u_surname'] ?? '';
        $u_givenname  = $application['u_givenname'] ?? '';
        $u_email      = $application['u_relative_email'] ?? $application['u_email'] ?? "tlspay-{$client}-{$fg_id}@tlscontact.com";
        $params       = [
            'merchantTransactionID' => str_replace('-', '_', $transaction['t_transaction_id']),
            'requestAmount'         => $transaction['t_amount'],
            'currencyCode'          => $transaction['t_currency'],
            'accountNumber'         => $fg_id,
            'serviceCode'           => $tingg_config['serviceCode'],
            'dueDate'               => $transaction['t_expiration'],
            'requestDescription'    => 'Tlscontact fees for group ' . $fg_id,
            'countryCode'           => strtoupper(substr($issuer, 0, 2)),
            'languageCode'          => 'en',
            "customerLastName"      => $u_surname,
            "customerFirstName"     => $u_givenname,
            'customerEmail'         => $u_email,
            'successRedirectUrl'    => get_callback_url($tingg_config['successRedirectUrl']),
            'failRedirectUrl'       => get_callback_url($tingg_config['failRedirectUrl']) . $t_id,
            'paymentWebhookUrl'     => get_callback_url($tingg_config['paymentWebhookUrl'])
        ];

        $encryptParams = $this->encrypt($tingg_config['ivKey'], $tingg_config['secretKey'], $params);

        $queryParams = [
            'accessKey'   => $tingg_config['accessKey'],
            'params'      => $encryptParams,
            'countryCode' => strtoupper(substr($issuer, 0, 2))
        ];

        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $transaction);

        return [
            'form_method' => 'get',
            'form_action' => $tingg_config['host'],
            'form_fields' => $queryParams
        ];
    }

    public function return($params)
    {
        if(empty($params['merchantTransactionID']) || empty($params['serviceCode'])) {
            return [
                'status' => 'error',
                'message' => 'no_data_received'
            ];
        }
        Log::info('return:$params:'.json_encode($params));
        $transaction_id = str_replace('_', '-', $params['merchantTransactionID']);
        $this->paymentService->saveTransactionLog($transaction_id, $params, $this->getPaymentGatewayName());
        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $transaction_id]);
        if (empty($transaction)) {
            Log::warning("ONLINE PAYMENT, TINGG : No transaction found in the database for " . $transaction_id . "\n" .
                json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return [
                'status' => 'error',
                'message' => 'transaction_id_not_exists'
            ];
        }
        $tingg_config = $this->getTinggConfig($transaction, $transaction['t_xref_pa_id']);
        $bearer_token = $this->apiService->getTinggAuthorization($tingg_config);
        Log::info('return:$bearer_token:'.json_encode($bearer_token));
        $response = $this->apiService->getTinggQueryStatus($params, $bearer_token, $tingg_config);
        Log::info('return:$response:'.json_encode($response));
        if(!empty($response['status']) && $response['status'] == 200) {
            $payment = $response['body']['results'];
            $confirm_params = [
                'gateway' => $this->getPaymentGatewayName(),
                'amount' => $payment['amountPaid'] ?? '',
                'currency' => $payment['paymentCurrencyCode'] ?? 0,
                'transaction_id' => $transaction_id,
                'gateway_transaction_id' => current($payment['payments'])['payerTransactionID'] ?? '',
            ];
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $response,'success');
            return $this->paymentService->confirm($transaction, $confirm_params);
        } else {
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $response,'fail');
            return [
                'status' => 'error',
                'message' => 'transaction_id_not_exists'
            ];
        }
    }

    private function getTinggConfig($transaction, $pa_id) {
        $client       = $transaction['t_client'];
        $issuer       = $transaction['t_issuer'];
        $config       = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $pa_id);
        return array_merge($config['common'], $this->getPaySecret($config));
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

    private function encrypt($ivKey, $secretKey, $payload = [])
    {
        //The encryption method to be used
        $encrypt_method = "AES-256-CBC";

        // Hash the secret key
        $key = hash('sha256', $secretKey);

        // Hash the iv - encrypt method AES-256-CBC expects 16 bytes
        $iv = substr(hash('sha256', $ivKey), 0, 16);

        $encrypted = openssl_encrypt(
            json_encode($payload, true), $encrypt_method, $key, 0, $iv
        );

        //Base 64 Encode the encrypted payload
        $encryptedPayload = base64_encode($encrypted);

        return $encryptedPayload;
    }

}
