<?php

namespace App\Services;


class GatewayService
{
    protected $paymentGatewayService;

    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }

    public function getGateways($client, $issuer, $service = 'tls')
    {
        $getClientUseUi = $this->getClientUseFile();
        if ($getClientUseUi) {
            $config = $this->paymentGatewayService->getConfig($client, $issuer, $service);
        } else {
            $config = $this->getConfig($client, $issuer);
        }
        return $config ?? [];
    }

    public function getGateway($client, $issuer, $gateway, $pa_id = null)
    {
        $getClientUseUi = $this->getClientUseFile();
        if ($getClientUseUi) {
            return $this->paymentGatewayService->getPaymentAccountConfig($gateway, $pa_id);
        } else {
            return config('payment_gateway')[$client][$issuer][$gateway] ?? [];
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
        $country = substr($issuer, 0, 2);
        $payment_client = substr($issuer, -2);
        $country_level_config = $country . 'All2' . $payment_client;
        $client_payment_gateway = config('payment_gateway')[$client];
        if (!empty($client_payment_gateway[$issuer])) {
            $config = $client_payment_gateway[$issuer];
        } elseif (!empty($client_payment_gateway[$country_level_config])) {
            $config = $client_payment_gateway[$country_level_config];
        } else {
            $config = [];
        }
        // allAll2all
        $all_issuer = 'allAll2all';
        if (isset($client_payment_gateway[$all_issuer]) && !empty($client_payment_gateway[$all_issuer])) {
            $all_config = $client_payment_gateway[$all_issuer];
            foreach (array_keys($all_config) as $key) {
                if (!in_array($key, array_keys($config))) {
                    $config = array_merge($config, $all_config);
                }
            }
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
        return $config_data;
    }
}
