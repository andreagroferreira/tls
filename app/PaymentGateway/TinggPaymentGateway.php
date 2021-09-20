<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\ApiService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TinggPaymentGateway implements PaymentGatewayInterface
{
    private $transactionService;
    private $gatewayService;
    private $paymentService;
    private $apiService;
    private $guzzleClient;

    public function __construct(
        TransactionService $transactionService,
        GatewayService $gatewayService,
        PaymentService $paymentService,
        Client $guzzleClient,
        ApiService $apiService
    )
    {
        $this->transactionService = $transactionService;
        $this->gatewayService     = $gatewayService;
        $this->paymentService     = $paymentService;
        $this->apiService         = $apiService;
        $this->guzzleClient       = $guzzleClient;
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
        return $this->return($params);
    }

    public function redirto($t_id)
    {
        $transaction = $this->transactionService->getTransaction($t_id);
        if (empty($transaction)) {
            return [
                'status' => 'error',
                'message' => 'transaction_id_not_exists',
            ];
        }
        $client       = $transaction['t_client'];
        $issuer       = $transaction['t_issuer'];
        $fg_id        = $transaction['t_xref_fg_id'];
        $tingg_config = $this->getTinggConfig($transaction);
        $application  = $this->apiService->callTlsApi('GET', '/tls/v2/' . $client . '/form_group/' . $fg_id);
        $u_surname    = $application['body']['u_surname'] ?? '';
        $u_givenname  = $application['body']['u_givenname'] ?? '';
        $u_email      = $application['body']['u_relative_email'] ?? $application['body']['u_email'] ?? "tlspay-{$client}-{$fg_id}@tlscontact.com";
        $params       = [
            'merchantTransactionID' => str_replace('-', '_', $transaction['t_transaction_id']),
            'requestAmount'         => $transaction['t_amount'],
            'currencyCode'          => $transaction['t_currency'],
            'accountNumber'         => $tingg_config['accountNumber'],
            'serviceCode'           => $tingg_config['serviceCode'],
            'dueDate'               => Carbon::now()->addMinutes(30)->format('Y-m-d H:i:s'),
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
        return [
            'form_method' => 'get',
            'form_action' => $tingg_config['host'],
            'form_fields' => $queryParams,
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

        $transaction_id = str_replace('_', '-', $params['merchantTransactionID']);
        $this->paymentService->saveTransactionLog($transaction_id, $params, $this->getPaymentGatewayName());

        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $transaction_id]);
        if (empty($transaction)) {
            Log::warning("ONLINE PAYMENT, CMI : No transaction found in the database for " . $transaction_id . "\n" .
                json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return [
                'status' => 'error',
                'message' => 'transaction_id_not_exists'
            ];
        }
        $tingg_config = $this->getTinggConfig($transaction);
        $bearer_token = $this->getAuthorization($tingg_config);
        $response = $this->queryStatus($params, $bearer_token);
        if(!empty($response['status']) && $response['status'] == 200) {
            $payment = $response['body']['results'];
            $confirm_params = [
                'gateway' => $this->getPaymentGatewayName(),
                'amount' => $payment['amountPaid'] ?? '',
                'currency' => $payment['paymentCurrencyCode'] ?? 0,
                'transaction_id' => $transaction_id,
                'gateway_transaction_id' => current($payment['payments'])['payerTransactionID'] ?? '',
            ];
            return $this->paymentService->confirm($transaction, $confirm_params);
        } else {
            return [
                'status' => 'error',
                'message' => 'transaction_id_not_exists'
            ];
        }
    }

    private function getTinggConfig($transaction) {
        $client       = $transaction['t_client'];
        $issuer       = $transaction['t_issuer'];
        $config       = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName());
        return array_merge($config['common'], $this->isSandbox() ? $config['sandbox'] : $config['prod']);
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

    private function getAuthorization($tingg_config) {
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' =>  $tingg_config['clientID'],
            "client_secret" => $tingg_config['clientSecret'],
        ];
        $response = $this->guzzleClient->request('POST', 'https://developer.tingg.africa/checkout/v2/custom/oauth/token', [
            'verify' => env('VERIFYPEER'),
            'http_errors' => false,
            'idn_conversion' => false,
            'Accept' => 'application/json',
            'json' => $data,
        ]);
        $response = [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true)
        ];
        return $response['body']['access_token'] ?? '';
    }

    private function queryStatus($params, $bearer_token) {
        $data = [
            'merchantTransactionID' => $params['merchantTransactionID'],
            'serviceCode' => $params['serviceCode']
        ];
        $response = $this->guzzleClient->request('POST', 'https://developer.tingg.africa/checkout/v2/custom/requests/query-status', [
            'verify' => env('VERIFYPEER'),
            'http_errors' => false,
            'idn_conversion' => false,
            'Accept' => 'application/json',
            'headers' => [
                'Authorization' => 'Bearer ' . $bearer_token,
                'Content-Type' => 'application/json'
            ],
            'json' => $data,
        ]);
        $response = [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true)
        ];
        return $response;
    }

}
