<?php

namespace App\Services;

use App\Repositories\PaymentAccountsRepositories;
use App\Repositories\PaymentServiceProvidersRepositories;

class PaymentAccountsService
{
    protected $paymentAccountsRepositories;
    protected $paymentServiceProvidersRepositories;
    protected $dbConnectionService;

    public function __construct(
        PaymentAccountsRepositories $paymentAccountsRepositories,
        PaymentServiceProvidersRepositories $paymentServiceProvidersRepositories,
        DbConnectionService $dbConnectionService
    ) {
        $this->paymentAccountsRepositories = $paymentAccountsRepositories;
        $this->paymentAccountsRepositories->setConnection($dbConnectionService->getConnection());
        $this->paymentServiceProvidersRepositories = $paymentServiceProvidersRepositories;
        $this->paymentServiceProvidersRepositories->setConnection($dbConnectionService->getConnection());
    }

    public function update($params): object
    {
        return $this->paymentAccountsRepositories->update(['pa_id' => $params['pa_id']], $params);
    }

    public function fetch($where, $field = '*')
    {
        $paymentAccounts = $this->paymentAccountsRepositories->fetch(
            $where,
            ['pa_id', 'pa_xref_psp_id', 'pa_type', 'pa_name', 'pa_info']
        );
        $paymentAccountsInfo = $paymentAccounts->toArray();
        $paymentAccountsInfo['pa_info'] = empty($paymentAccountsInfo['pa_info']) ? [] : get_object_vars(json_decode($paymentAccountsInfo['pa_info']));
        $paymentServiceProviders = $this->paymentServiceProvidersRepositories->fetch(
            ['psp_id' => $paymentAccounts['pa_xref_psp_id']],
            ['psp_code', 'psp_name']
        );

        return array_merge($paymentAccountsInfo, $paymentServiceProviders->toArray());
    }

    public function fetchPaymentServiceProvidersList(): array
    {
        $paymentServiceProviders = $this->paymentServiceProvidersRepositories->fetchAll(['psp_id', 'psp_code', 'psp_name']);

        return $paymentServiceProviders->toArray();
    }

    public function create($params): object
    {
        return $this->paymentAccountsRepositories->create($params);
    }
}
