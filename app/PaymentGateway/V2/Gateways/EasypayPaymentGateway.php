<?php

namespace App\PaymentGateway\V2\Gateways;

use App\Contracts\PaymentGateway\V2\PaymentGatewayInterface;
use App\PaymentGateway\V2\PaymentGateway;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class EasypayPaymentGateway extends PaymentGateway implements PaymentGatewayInterface
{
    protected $config;
    protected $environment;
    protected $appToken;
    protected $pageToken;
    protected $headers = [];

    /**
     * EasypayPaymentGateway constructor.
     *
     * @param array $config
     */
    public function __construct()
    {
        // Set the config for this payment gateway probably should go to another class/method
        $this->config = config('payment_gateway.pl.uaAll2pl.easypay');
        $this->headers = [
            'PartnerKey' => $this->config['partner_key'],
            'locale' => $this->config['locale'],
        ];
        $this->environment = app()->environment();
    }

    /**
     * Charge a customer's credit card.
     *
     * @param float $amount
     * @param array $options
     *
     * @return mixed
     */
    public function charge(float $amount, array $options = [])
    {
        $this->checkOrCreateAppToken();
        $this->refreshPageToken();
        $this->createOrder($options['transaction_id']);
    }

    /**
     * Refund a charged credit card.
     *
     * @param float  $amount
     * @param string $transactionId
     *
     * @return mixed
     */
    public function refund(float $amount, $transactionId)
    {
        // Implementation to refund a charged credit card using Ukrainian Easypay
    }

    /**
     * Cancel a pending charge.
     *
     * @param string $transactionId
     *
     * @return mixed
     */
    public function cancel($transactionId)
    {
        // Implementation to cancel a pending charge using Ukrainian Easypay
    }

    /**
     * Check if the app token exists, if not create a new one.
     * This app token has a lifetime of 90 days (3 months).
     *
     * @return string
     */
    protected function checkOrCreateAppToken(): string
    {
        if ($this->appToken) {
            return $this->appToken;
        }

        $response = Http::withHeaders($this->headers)
            ->post($this->config['base_url'].'/system/createApp')
            ->json();

        $this->pageToken = $response['pageId'];

        $this->updateHeaders(['AppId' => $response['appId']]);

        return $this->appToken = $response['appId'];
    }

    /**
     * Generate a new page token.
     * This needs to be called before every request, and has a lifetime of 20 minutes.
     *
     * @return string
     */
    protected function refreshPageToken(): string
    {
        $response = Http::withHeaders($this->headers)
            ->post($this->config['base_url'].'/system/createPage')
            ->json();

        $this->updateHeaders(['PageId' => $response['pageId']]);

        return $this->pageToken = $response['pageId'];
    }

    protected function createOrder(string $transactionId): array
    {
        $body = [
            'order' => [
                'serviceKey' => $this->config['sandbox']['service_key'],
                'orderId' => $transactionId,
                'description' => 'Generic description',
                'amount' => 1.00,
            ],
        ];
        $signature = base64_encode(hash('sha256', $this->config['sandbox']['secret_key'].json_encode($body), true));
        $response = Http::withHeaders(
            $this->updateHeaders([
                // 'ServiceKey' => $this->config['sandbox']['service_key'],
                // 'SecretKey' => $this->config['sandbox']['secret_key'],
                'Sign' => $signature,
            ])
        )
            ->post($this->config['base_url'].'/merchant/createOrder', $body)
            ->json();

        dd($response);
    }

    /**
     * Update the headers being sent with the request.
     * These headers are used for authentication.
     *
     * @param array $headers
     *
     * @return array
     */
    private function updateHeaders(array $headers = []): array
    {
        $this->headers = Arr::collapse([
            $this->headers,
            $headers,
        ]);

        return $this->headers;
    }
}
