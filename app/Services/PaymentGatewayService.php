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

}
