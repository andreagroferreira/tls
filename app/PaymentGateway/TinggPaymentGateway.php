<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\ApiService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TinggPaymentGateway implements PaymentGatewayInterface
{
    private $transactionService;
    private $gatewayService;
    private $paymentService;
    private $apiService;

    public function __construct(
        TransactionService $transactionService,
        GatewayService $gatewayService,
        PaymentService $paymentService,
        ApiService $apiService
    )
    {
        $this->transactionService = $transactionService;
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
        $config       = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName());
        $tingg_config = array_merge($config['common'], $this->isSandbox() ? $config['sandbox'] : $config['prod']);
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
            'countryCode'           => $this->isSandBox() ? 'KE' : strtoupper(substr($issuer, 0, 2)),
            'languageCode'          => 'en',
            "customerLastName"      => $u_surname,
            "customerFirstName"     => $u_givenname,
            'customerEmail'         => $u_email,
            'successRedirectUrl'    => url($tingg_config['successRedirectUrl']),
            'failRedirectUrl'       => url($tingg_config['failRedirectUrl']) . $t_id,
            'paymentWebhookUrl'     => url($tingg_config['paymentWebhookUrl'])
        ];

        $encryptParams = $this->encrypt($tingg_config['ivKey'], $tingg_config['secretKey'], $params);

        $queryParams = [
            'accessKey'   => $tingg_config['accessKey'],
            'params'      => $encryptParams,
            'countryCode' => $this->isSandBox() ? 'KE' : strtoupper(substr($issuer, 0, 2))
        ];
        return [
            'status' => 'success',
            'url' => $tingg_config['host'] . '?' . http_build_query($queryParams),
        ];
    }

    public function return($params)
    {
        if(empty($params['payments'])) {
            return [
                'status' => 'error',
                'message' => 'no_data_received'
            ];
        }

        $payments = current(json_decode($params['payments']));
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

        $confirm_params = [
            'gateway' => $this->getPaymentGatewayName(),
            'amount' => $payments->amountPaid,
            'currency' => $payments->currencyCode,
            'transaction_id' => $transaction_id,
            'gateway_transaction_id' => $payments->payerTransactionID,
        ];
        return $this->paymentService->confirm($transaction, $confirm_params);
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
