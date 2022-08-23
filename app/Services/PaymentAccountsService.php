<?php

namespace App\Services;

use App\Repositories\PaymentAccountsRepositories;
use App\Repositories\PaymentConfigurationsRepositories;
use App\Repositories\PaymentServiceProvidersRepositories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentAccountsService
{
    protected $paymentAccountsRepositories;
    protected $paymentServiceProvidersRepositories;
    protected $paymentConfigurationsRepositories;
    protected $dbConnectionService;

    public function __construct(
        PaymentAccountsRepositories $paymentAccountsRepositories,
        PaymentServiceProvidersRepositories $paymentServiceProvidersRepositories,
        PaymentConfigurationsRepositories $paymentConfigurationsRepositories,
        DbConnectionService $dbConnectionService
    )
    {
        $this->dbConnectionService = $dbConnectionService;
        $this->paymentAccountsRepositories = $paymentAccountsRepositories;
        $this->paymentAccountsRepositories->setConnection($dbConnectionService->getConnection());
        $this->paymentServiceProvidersRepositories = $paymentServiceProvidersRepositories;
        $this->paymentServiceProvidersRepositories->setConnection($dbConnectionService->getConnection());
        $this->paymentConfigurationsRepositories = $paymentConfigurationsRepositories;
        $this->paymentConfigurationsRepositories->setConnection($dbConnectionService->getConnection());
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
        $paymentAccountsInfo['pa_info'] = get_object_vars(json_decode($paymentAccounts->toArray()['pa_info']));
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

    public function create($params)
    {
        $db_connection = DB::connection($this->dbConnectionService->getConnection());
        $db_connection->beginTransaction();
        try{
            $accountData = [
                'pa_xref_psp_id' => $params['pa_xref_psp_id'],
                'pa_name' => $params['pa_name'],
                'pa_type' => $params['pa_type'],
                'pa_info' => $params['pa_info'],
            ];
            $accountInfo = $this->paymentAccountsRepositories->create($accountData)->toArray();
            $configurationsData = [
                'pc_xref_pa_id' => $accountInfo['pa_id'],
                'pc_project'    => $params['pc_project'],
                'pc_city'       => $params['pc_city'],
                'pc_country'    => $params['pc_country'],
                'pc_service'    => $params['pc_service'],
            ];
            $configurationsInfo = $this->paymentConfigurationsRepositories->create($configurationsData)->toArray();
            $db_connection->commit();
            return array_merge($accountInfo, $configurationsInfo);
        } catch (\Exception $e) {
            $db_connection->rollBack();
            return $e->getMessage();
        }
    }

}
