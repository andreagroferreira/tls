<?php

namespace App\Services;

class GatewayService
{
    /**
     * @var PaymentGatewayService
     */
    protected $paymentGatewayService;

    /**
     * @param PaymentGatewayService $paymentGatewayService
     */
    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }

    public function getGateways($client, $issuer, $service = 'tls')
    {
        $getClientUseFile = $this->getClientUseFile();
        if ($getClientUseFile) {
            return $this->getConfig($client, $issuer);
        }

        return $this->paymentGatewayService->getConfig($client, $issuer, $service);
    }

    /**
     * @param string   $client
     * @param string   $issuer
     * @param string   $gateway
     * @param null|int $pa_id
     * @param string   $service
     *
     * @return array
     */
    public function getGateway(
        string $client,
        string $issuer,
        string $gateway,
        ?int $pa_id = null,
        string $service = 'tls'
    ): array {
        $getClientUseFile = $this->getClientUseFile();
        if ($getClientUseFile) {
            return $this->getConfig($client, $issuer)[$gateway] ?? [];
        }

        $config = $this->paymentGatewayService->getPaymentAccountConfig(
            $gateway,
            $issuer,
            $pa_id,
            $service
        );
        $diff = array_diff_key(config("payment_gateway_accounts.{$gateway}." . $config['pa_type']), $config['config']);
        foreach ($diff as $key => $value) {
            $config['config'][$key] = $value;
        }

        return $config;
    }

    public function getClientUseFile(): bool
    {
        $current_client = env('CLIENT');
        $clients = explode(',', env('USE_FILE_CONFIGURATION'));
        if (in_array($current_client, $clients)) {
            return true;
        }

        return false;
    }

    public function getConfig($client, $issuer)
    {
        $config = [];
        $country = substr($issuer, 0, 2);
        $paymentClient = substr($issuer, -2);
        $defaultClientConfiguration = 'allAll2all';
        $countryLevelConfiguration = $country . 'All2' . $paymentClient;

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

    public function getKbankConfig($client, $issuer, $gateway, $pa_id)
    {
        $kbank_config = $this->getGateway($client, $issuer, $gateway, $pa_id);
        $getClientUseFile = $this->getClientUseFile();
        if ($getClientUseFile) {
            $app_env = !(env('APP_ENV') === 'production');
            $is_live = $kbank_config['common']['env'] == 'live';
            if ($is_live && !$app_env) {
                $config_data = [
                    'redirect_host' => $kbank_config['production']['redirect_host'],
                    'api_key' => $kbank_config['production']['apikey'],
                    'mid' => $kbank_config['production']['mid'],
                ];
            } else {
                $config_data = [
                    'redirect_host' => $kbank_config['sandbox']['sandbox_redirect_host'],
                    'api_key' => $kbank_config['sandbox']['sandbox_apikey'],
                    'mid' => $kbank_config['sandbox']['sandbox_mid'],
                ];
            }
        } else {
            $config_data = [
                'redirect_host' => $kbank_config['config']['redirect_host'],
                'api_key' => $kbank_config['config']['apikey'],
                'mid' => $kbank_config['config']['mid'],
            ];
        }

        return $config_data;
    }
}
