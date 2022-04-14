<?php


namespace App\Services;


class GatewayService
{
    public function getGateways($client, $issuer)
    {
        $all_gateway = config('payment_gateway');
        if (!array_key_exists($issuer, $all_gateway[$client])) {
            $issuer = 'allAll2all';
        }
        return $all_gateway[$client][$issuer] ?? [];
    }

    public function getGateway($client, $issuer, $gateway)
    {
        return config('payment_gateway')[$client][$issuer][$gateway] ?? [];
    }

    public function getKbankConfig($client, $issuer, $gateway)
    {
        $kbank_config = $this->getGateway($client, $issuer, $gateway);
        $app_env = env('APP_ENV') === 'production' ? false : true;
        $is_live = $kbank_config['common']['env'] == 'live' ? true : false;
        if ($is_live && !$app_env) {
            $config_data = [
                'redirect_host' => $kbank_config['prod']['redirect_host'],
                'api_key' => $kbank_config['prod']['apikey'],
                'mid' => $kbank_config['prod']['mid']
            ];
        } else {
            $config_data = [
                'redirect_host' => $kbank_config['sandbox']['sandbox_redirect_host'],
                'api_key' => $kbank_config['sandbox']['sandbox_apikey'],
                'mid' => $kbank_config['sandbox']['sandbox_mid']
            ];
        }
        return $config_data;
    }
}
