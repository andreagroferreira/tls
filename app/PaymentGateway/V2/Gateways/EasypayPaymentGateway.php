<?php

namespace App\PaymentGateway\V2\Gateways;

use App\Contracts\PaymentGateway\V2\PaymentGatewayInterface;
use App\Models\PaymentAccounts;
use App\Models\PaymentConfigurations;
use App\Models\PaymentServiceProviders;
use App\PaymentGateway\V2\PaymentGateway;
use App\Repositories\PaymentAccountsRepositories;
use App\Repositories\PaymentConfigurationsRepositories;
use App\Repositories\PaymentServiceProvidersRepositories;
use App\Services\ApiService;
use App\Services\DbConnectionService;
use App\Services\GatewayService;
use App\Services\PaymentGatewayService;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EasypayPaymentGateway extends PaymentGateway implements PaymentGatewayInterface
{
    protected $config;
    protected $appToken;
    protected $pageToken;
    protected $environment;
    protected $headers = [];

    /**
     * EasypayPaymentGateway constructor.
     */
    public function __construct()
    {
        // Set the config for this payment gateway probably should go to another class/method
        // $this->config = config('payment_gateway.pl.uaAll2pl.easypay'); // TODO: Use GatewayService::getGateways() instead
        $this->environment = app()->environment();

        $this->config = current((new GatewayService(
            new PaymentGatewayService(
                new ApiService(new Client()),
                new PaymentAccountsRepositories(new PaymentAccounts()),
                new DbConnectionService(),
                new PaymentConfigurationsRepositories(new PaymentConfigurations()),
                new PaymentServiceProvidersRepositories(new PaymentServiceProviders())
            )
        ))->getGateways('de', 'keNBO2de'));

        $this->headers = [
            'PartnerKey' => $this->config[$this->environment]['partnerKey'],
            'locale' => $this->config[$this->environment]['locale'],
        ];
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

        return $this->createOrder($amount, $options);
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
            // TODO: Add rule to verify the 90 days TTL to refresh this token.
            return $this->appToken;
        }

        return $this->refreshAppToken();
    }

    /**
     * Creates a new appToken.
     *
     * @return string
     */
    protected function refreshAppToken(): string
    {
        $response = Http::withHeaders($this->headers)
            ->post($this->config[$this->environment]['host'].'/system/createApp')
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
            ->post($this->config[$this->environment]['host'].'/system/createPage')
            ->json();

        if ($this->hasErrors($response)) {
            $this->handle($response['error']['errorCode']);
        }
        $this->updateHeaders(['PageId' => $response['pageId'] ?? $this->pageToken]);

        return $response['pageId'] ?? $this->pageToken;
    }

    protected function createOrder(float $amount, array $options): array
    {
        $body = [
            'order' => [
                'serviceKey' => $this->config[$this->environment]['serviceKey'],
                'orderId' => $options['transaction_id'],
                'description' => 'Generic description',
                'amount' => $this->isSandbox() ? 1.0 : $amount,
                'additionalItems' => [
                    'Merchant.UrlNotify' => get_callback_url($this->config['common']['successRedirectUrl']),
                ],
            ],
        ];

        $response = Http::withHeaders(
            $this->updateHeaders([
                'Sign' => $this->generateSign($body),
            ])
        )
            ->post($this->config[$this->environment]['host'].'/merchant/createOrder', $body)
            ->json();

        if ($this->hasErrors($response)) {
            Log::error('[PaymentGateway\EasypayPaymentGateway] - Error while creating an order.', [
                'error' => $response['error'],
                'requestBody' => $body,
            ]);

            return [];
            // TODO: Error handling
            /*
             * PROVIDER_ERROR_DUBLICATED_ORDER_ID
             * AMOUNT_VALIDATION_BY_SERVICE_EXCEPTION
             */
        }

        return [
            'form_method' => 'get',
            'form_action' => $response['forwardUrl'],
            'form_fields' => '',
        ];
    }

    /**
     * Handle errors regarding the provider response.
     *
     * @param string $errorCode
     */
    protected function handle(string $errorCode): void
    {
        switch ($errorCode) {
            case 'APPID_NOT_FOUND':
                $this->refreshAppToken();

                break;

            case 'PAGE_NOT_FOUND':
                $this->refreshPageToken();

                break;
        }
    }

    /**
     * Checks if the response has errors.
     *
     * @param array $response
     *
     * @return bool
     */
    protected function hasErrors(array $response): bool
    {
        return $response['error'] != null;
    }

    /**
     * Update the headers being sent with the request.
     * These headers are used for authentication.
     *
     * @param array $headers
     *
     * @return array
     */
    protected function updateHeaders(array $headers = []): array
    {
        return $this->headers = Arr::collapse([
            $this->headers,
            $headers,
        ]);
    }

    /**
     * Generate the sign for the request.
     *
     * @param array $requestBody the body of the request
     *
     * @return string
     */
    protected function generateSign(array $requestBody): string
    {
        return base64_encode(
            hash(
                'sha256',
                $this->config[$this->environment]['secretKey'].json_encode($requestBody),
                true
            )
        );
    }
}
