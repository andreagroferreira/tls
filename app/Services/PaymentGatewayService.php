<?php

namespace App\Services;

use App\Repositories\PaymentAccountsRepositories;
use App\Repositories\PaymentConfigurationsRepositories;
use App\Repositories\PaymentServiceProvidersRepositories;

class PaymentGatewayService
{
    protected $apiService;
    protected $projectId;
    protected $paymentAccountsRepositories;
    protected $paymentConfigurationsRepositories;
    protected $paymentServiceProvidersRepositories;

    public function __construct(
        ApiService $apiService,
        PaymentAccountsRepositories $paymentAccountsRepositories,
        DbConnectionService $dbConnectionService,
        PaymentConfigurationsRepositories $paymentConfigurationsRepositories,
        PaymentServiceProvidersRepositories $paymentServiceProvidersRepositories
    )
    {
        $this->apiService = $apiService;
        $this->projectId = $this->apiService->getProjectId();
        $this->paymentAccountsRepositories = $paymentAccountsRepositories;
        $this->paymentAccountsRepositories->setConnection($dbConnectionService->getConnection());
        $this->paymentConfigurationsRepositories = $paymentConfigurationsRepositories;
        $this->paymentConfigurationsRepositories->setConnection($dbConnectionService->getConnection());
        $this->paymentServiceProvidersRepositories = $paymentServiceProvidersRepositories;
        $this->paymentServiceProvidersRepositories->setConnection($dbConnectionService->getConnection());
    }

    public function getPaymentAccountConfig($client, $issuer, $gateway, $service): array
    {
        $where = [
            'pc_project'    => $client,
            'pc_country'    => substr($issuer, 0, 2),
            'pc_city'       => substr($issuer, 2, 3),
            'pc_service'    => $service,
            'pc_is_actived' => true
        ];

        $paymentConfigurations = $this->paymentConfigurationsRepositories->findBy($where)->toArray();
        if (empty($paymentConfigurations)) {
            return [];
        }

        $pa_id_list = array_column($paymentConfigurations, 'pc_xref_pa_id');
        $paymentServiceProviders = $this->paymentServiceProvidersRepositories->fetch(['psp_code' => $gateway])->toArray();
        if (empty($paymentServiceProviders['psp_id'])) {
            return [];
        }

        $paymentAccounts = $this->paymentAccountsRepositories->fetchByIdAndPspId($pa_id_list, $paymentServiceProviders['psp_id'])->toArray();
        if (empty($paymentAccounts)) {
            return [];
        }

        $pa_type = env('APP_ENV') === 'production' ? 'prod' : 'sandbox';
        $paymentAccounts = array_filter($paymentAccounts, function ($item) use ($pa_type) {
            return $item['pa_type'] === $pa_type;
        });
        if (empty(current($paymentAccounts)['pa_info'])) {
            return [];
        }

        $paymentAccounts = json_decode(current($paymentAccounts)['pa_info'], true);

        $result = [];
        $result['label']    = config("payment_gateway_accounts.$gateway.label");
        $result['common']   = config("payment_gateway_accounts.$gateway.common");
        $result[$pa_type]   = $paymentAccounts;
        return $result;
    }

    public function getConfig($client, $issuer, $service)
    {
        $country = substr($issuer, 0, 2);
        $country_level_config = $country . 'All2' . $client;
        $global_config = 'allall2' . $client;
        $getPaymentGatewayConfig = $this->getPaymentGatewayConfig($client, $issuer, $service);
        if (empty($getPaymentGatewayConfig)) {
            $getPaymentGatewayConfig = $this->getPaymentGatewayConfig($client, $country_level_config, $service);
            if (empty($getPaymentGatewayConfig)) {
                $getPaymentGatewayConfig = $this->getPaymentGatewayConfig($client, $global_config, $service);
            }
        }
        return $getPaymentGatewayConfig ?? [];
    }

    public function getPaymentGatewayConfig($client, $issuer, $service): array
    {
        if (substr($issuer, 0, 3) == 'all') {
            $pc_country = substr($issuer, 0, 3);
            $pc_city = substr($issuer, 3, 3);
        } else {
            $pc_country = substr($issuer, 0, 2);
            $pc_city = substr($issuer, 2, 3);
        }
        $where = [
            'pc_project' => $client,
            'pc_country' => $pc_country,
            'pc_city' => $pc_city,
            'pc_service' => $service,
            'pc_is_actived' => true
        ];

        $paymentConfigurations = $this->paymentConfigurationsRepositories->findBy($where)->toArray();
        if (empty($paymentConfigurations)) {
            return [];
        }
        $payment_gateway = [];
        foreach ($paymentConfigurations as $k => $v) {
            $payment_gateway[] = $this->paymentAccountsRepositories->fetchById($v['pc_xref_pa_id'])->toArray();
        }
        $payment_gateway_config = [];
        foreach ($payment_gateway as $key => $values) {
            $payment_gateway[$key]['psp_code'] = $this->paymentServiceProvidersRepositories->fetch(['psp_id' => $values['pa_xref_psp_id']], 'psp_code')['psp_code'];
        }
        foreach ($payment_gateway as $k => $v) {
            $gateway = $v['psp_code'];
            if ($v['psp_code'] != 'pay_later') {
                $gateway_type = $v['psp_code'] . '_' . $v['pa_type'];
            } else {
                $gateway_type = $v['psp_code'];
            }
            $payment_gateway_config[$gateway_type]['pa_id'] = $v['pa_id'];
            $payment_gateway_config[$gateway_type]['psp_code'] = $gateway;
            $payment_gateway_config[$gateway_type]['label'] = config("payment_gateway_accounts.$gateway.label");
            $payment_gateway_config[$gateway_type]['type'] = $v['pa_type'];
            $payment_gateway_config[$gateway_type]['common'] = config("payment_gateway_accounts.$gateway.common");
            $payment_gateway_config[$gateway_type][$v['pa_type']] = json_decode($v['pa_info'], true);
            $payment_gateway_config[$gateway_type]['sort'] = ($gateway == 'pay_later' ? 2 : 1);
        }
        $pa_name = array_column($payment_gateway_config, 'psp_code');
        array_multisort($pa_name, SORT_ASC, $payment_gateway_config);
        $sort_payment_gateway_config = collect($payment_gateway_config);
        $payment_gateway_config = $sort_payment_gateway_config->sortBy('sort')->toArray();
        return $payment_gateway_config ?? [];
    }


}
