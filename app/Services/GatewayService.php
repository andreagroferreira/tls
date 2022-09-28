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
        return $this->getConfig($client, $issuer)[$gateway] ?? [];
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
