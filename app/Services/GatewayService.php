<?php

namespace App\Services;

class GatewayService
{
    /**
     * @var PaymentGatewayService
     */
    protected PaymentGatewayService $paymentGatewayService;

    /**
     * @param PaymentGatewayService $paymentGatewayService
     */
    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * @param string $client
     * @param string $issuer
     * @param string $service
     *
     * @return array
     */
    public function getGateways(
        string $client,
        string $issuer,
        string $service = 'tls'
    ): array {
        if (env('USE_UI_CONFIGURATION')) {
            return $this->paymentGatewayService->getConfig(
                $client,
                $issuer,
                $service
            );
        }

        return $this->getConfig($client, $issuer);
    }

    /**
     * @param string $client
     * @param string $issuer
     * @param string $gateway
     * @param string $service
     *
     * @return array
     */
    public function getGateway(
        string $client,
        string $issuer,
        string $gateway,
        string $service = 'tls'
    ): array {
        if (env('USE_UI_CONFIGURATION')) {
            return $this->paymentGatewayService->getPaymentAccountConfig(
                $client,
                $issuer,
                $gateway,
                $service
            );
        }

        return config('payment_gateway')[$client][$issuer][$gateway] ?? [];
    }

    /**
     * @param string $client
     * @param string $issuer
     *
     * @return array
     */
    public function getConfig(string $client, string $issuer): array
    {
        $config = [];
        $country = substr($issuer, 0, 2);
        $paymentClient = substr($issuer, -2);
        $defaultClientConfiguration = 'allAll2all';
        $countryLevelConfiguration = $country.'All2'.$paymentClient;

        $clientPaymentGateways = config('payment_gateway')[$client];

        if (!empty($clientPaymentGateways[$issuer])) {
            $config = $clientPaymentGateways[$issuer];
        } elseif (!empty($clientPaymentGateways[$countryLevelConfiguration])) {
            $config = $clientPaymentGateways[$countryLevelConfiguration];
        } elseif (!empty($clientPaymentGateways[$defaultClientConfiguration])) {
            $config = $clientPaymentGateways[$defaultClientConfiguration];
        }

        return $config;
    }

    /**
     * @param string $client
     * @param string $issuer
     * @param string $gateway
     *
     * @return array
     */
    public function getKbankConfig(
        string $client,
        string $issuer,
        string $gateway
    ): array {
        $kbankConfig = $this->getGateway($client, $issuer, $gateway);
        $appEnv = !('production' === env('APP_ENV'));
        $isLive = 'live' == $kbankConfig['common']['env'];

        if ($isLive && !$appEnv) {
            return [
                'redirect_host' => $kbankConfig['prod']['redirect_host'],
                'api_key' => $kbankConfig['prod']['apikey'],
                'mid' => $kbankConfig['prod']['mid'],
            ];
        }

        return [
            'redirect_host' => $kbankConfig['sandbox']['sandbox_redirect_host'],
            'api_key' => $kbankConfig['sandbox']['sandbox_apikey'],
            'mid' => $kbankConfig['sandbox']['sandbox_mid'],
        ];
    }
}
