<?php

namespace App\PaymentGateway\V2\Gateways;

use App\Contracts\PaymentGateway\V2\PaymentGatewayInterface;
use App\Models\Transactions;
use App\PaymentGateway\V2\PaymentGateway;
use App\Services\GatewayService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EasypayPaymentGateway extends PaymentGateway implements PaymentGatewayInterface
{
    /**
     * @var array
     */
    protected $headers;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $appToken;

    /**
     * @var string
     */
    protected $pageToken;

    /**
     * @var GatewayService
     */
    protected $gatewayService;

    /**
     * @var PaymentService
     */
    protected $paymentService;

    /**
     * EasypayPaymentGateway constructor.
     */
    public function __construct(GatewayService $gatewayService, PaymentService $paymentService)
    {
        $this->gatewayService = $gatewayService;
        $this->paymentService = $paymentService;
    }

    /**
     * Charge a customer's credit card.
     *
     * @param float $amount
     * @param array $options
     *
     * @return array
     */
    public function charge(float $amount, array $options = []): array
    {
        /** @var Transactions $transaction */
        $transaction = $options['transaction'];

        $this->config = $this->gatewayService->getGateway(
            $transaction->t_client,
            $transaction->t_issuer,
            $transaction->t_gateway,
            $transaction->t_xref_pa_id,
            $transaction->t_service
        );
        $this->headers = [
            'PartnerKey' => $this->config['config']['partnerKey'],
            'locale' => $this->config['config']['locale'],
        ];

        $this->checkOrCreateAppToken();
        $this->refreshPageToken();

        return $this->createOrder($amount, $options);
    }

    /**
     * @param Transactions $transaction
     * @param array        $transactionItems
     * @param float        $amount
     * @param Request      $request
     *
     * @throws \Exception
     *
     * @return array
     */
    public function callback(
        Transactions $transaction,
        array $transactionItems,
        float $amount,
        Request $request
    ): array {
        $this->config = $this->gatewayService->getGateway(
            $transaction->t_client,
            $transaction->t_issuer,
            $transaction->t_gateway,
            $transaction->t_xref_pa_id,
            $transaction->t_service
        );
        $this->headers = [
            'PartnerKey' => $this->config['config']['partnerKey'],
            'locale' => $this->config['config']['locale'],
        ];

        if (!$this->isRequestSignValid($request)) {
            throw new \Exception('[PaymentGateway\V2\Gateways\EasypayPaymentGateway] - Invalid Request Sign');
        }

        $paymentDetails = $request->get('details');
        $transactionData = array_merge($transaction->toArray(), [
            't_items' => $transactionItems,
            't_amount' => $amount,
        ]);

        return $this->paymentService->confirm($transactionData, [
            'gateway' => $transaction->t_gateway,
            'amount' => $paymentDetails['amount'],
            'currency' => $transaction->t_currency,
            'gateway_transaction_id' => $paymentDetails['payment_id'],
            'gateway_transaction_reference' => $paymentDetails['payment_id'],
        ]);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    protected function isRequestSignValid(Request $request): bool
    {
        $headerSign = $request->headers->get('sign');

        $sign = $this->generateSign($request->all());

        return $headerSign === $sign;
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
            ->post($this->config['config']['host'].'/system/createApp')
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
            ->post($this->config['config']['host'].'/system/createPage')
            ->json();

        if ($this->hasErrors($response)) {
            $this->handle($response['error']['errorCode']);
        }
        $this->updateHeaders(['PageId' => $response['pageId'] ?? $this->pageToken]);

        return $response['pageId'] ?? $this->pageToken;
    }

    /**
     * @param Transactions $transaction
     * @param float        $amount
     *
     * @return array
     */
    protected function generateRequestBody(Transactions $transaction, float $amount): array
    {
        return [
            'order' => [
                'serviceKey' => $this->config['config']['serviceKey'],
                'orderId' => $transaction->t_transaction_id,
                'description' => 'Generic description',
                'amount' => $this->isSandbox() ? 1.0 : $amount,
                'additionalItems' => [
                    'Merchant.UrlNotify' => get_callback_url($this->config['common']['notifyUrl']),
                ],
            ],
            'urls' => [
                'success' => get_callback_url($this->config['common']['successRedirectUrl']),
                'failed' => get_callback_url($this->config['common']['failedRedirectUrl']),
            ]
        ];
    }

    /**
     * @param float $amount
     * @param array $options
     *
     * @return array
     */
    protected function createOrder(float $amount, array $options): array
    {
        /** @var Transactions $transaction */
        $transaction = $options['transaction'];

        $body = $this->generateRequestBody($transaction, $amount);

        $sign = $this->generateSign($body);

        $response = Http::withHeaders(
            $this->updateHeaders([
                'Sign' => $sign,
            ])
        )
            ->post($this->config['config']['host'].'/merchant/createOrder', $body)
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
                $this->config['config']['secretKey'].json_encode($requestBody),
                true
            )
        );
    }
}
