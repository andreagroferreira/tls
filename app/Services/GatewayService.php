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
        } else {
            return $this->paymentGatewayService->getConfig($client, $issuer, $service);
        }
    }

    public function getGateway($client, $issuer, $gateway, $pa_id = null)
    {
        $getClientUseFile = $this->getClientUseFile();
        if ($getClientUseFile) {
            return config('payment_gateway')[$client][$issuer][$gateway] ?? [];
        } else {
            $config = $this->paymentGatewayService->getPaymentAccountConfig($gateway, $pa_id);
            $diff = array_diff_key(config("payment_gateway_accounts.$gateway." . $config['pa_type']), $config['config']);
            foreach ($diff as $key => $value) {
                $config['config'][$key] = $value;
            }
            return $config;
        }
    }

    public function getClientUseFile(): bool
    {
        $current_client = env('CLIENT');
        $clients = explode(',', env('USE_FILE_CONFIGURATION'));
        if (in_array($current_client, $clients)) {
            return true;
        } else {
            return false;
        }
    }

    public function getConfig($client, $issuer)
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

    public function getKbankConfig($client, $issuer, $gateway, $pa_id) {
        $kbank_config   = $this->getGateway($client, $issuer, $gateway, $pa_id);
        $app_env        = !(env('APP_ENV') === 'production');
        $is_live        = $kbank_config['common']['env'] == 'live';
        if ($this->getClientUseFile()) {
            $config_data = [
                'redirect_host' => $kbank_config['config']['redirect_host'] ?? $kbank_config['config']['redirect_host'] ?? '',
                'api_key'       => $kbank_config['config']['apikey'] ?? $kbank_config['config']['apikey'] ?? '',
                'mid'           => $kbank_config['config']['mid'] ?? $kbank_config['config']['mid'] ?? ''
            ];
        } else if ($is_live && !$app_env) {
            $config_data = [
                'redirect_host' => $kbank_config['prod']['redirect_host'],
                'api_key'       => $kbank_config['prod']['apikey'],
                'mid'           => $kbank_config['prod']['mid']
            ];
        } else {
            $config_data = [
                'redirect_host' => $kbank_config['sandbox']['sandbox_redirect_host'],
                'api_key'       => $kbank_config['sandbox']['sandbox_apikey'],
                'mid'           => $kbank_config['sandbox']['sandbox_mid']
            ];
        }

        return [
            'redirect_host' => $kbankConfig['sandbox']['sandbox_redirect_host'],
            'api_key' => $kbankConfig['sandbox']['sandbox_apikey'],
            'mid' => $kbankConfig['sandbox']['sandbox_mid'],
        ];
    }
}
