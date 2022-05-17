<?php


namespace App\Services;


class GatewayService
{
    public function getGateways($client, $issuer)
    {
        $config = $this->getConfig($client, $issuer);
        return $config ?? [];
    }

    public function getGateway($client, $issuer, $gateway) {
        return config('payment_gateway')[$client][$issuer][$gateway] ?? [];
    }

    public function getConfig($client, $issuer)
    {
        $country = substr($issuer, 0, 2);
        $payment_client = substr($issuer, -2);
        $country_level_config = $country . 'All2' . $payment_client;
        $global_config = 'allAll2all';
        $client_payment_gateway = config('payment_gateway')[$client];
        if (!empty($client_payment_gateway[$issuer])) {
            $config = $client_payment_gateway[$issuer];
        } elseif (!empty($client_payment_gateway[$country_level_config])) {
            $config = $client_payment_gateway[$country_level_config];
        } elseif (!empty($client_payment_gateway[$global_config])) {
            $config = $client_payment_gateway[$global_config];
        } else {
            $config = [];
        }
        return $config;
    }

    public function getKbankConfig($client, $issuer, $gateway) {
        $kbank_config   = $this->getGateway($client, $issuer, $gateway);
        $app_env        = env('APP_ENV') === 'production' ? false : true;
        $is_live        = $kbank_config['common']['env'] == 'live' ? true : false;
        if ($is_live && !$app_env) {
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
