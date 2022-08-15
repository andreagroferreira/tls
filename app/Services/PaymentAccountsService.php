<?php

namespace App\Services;

use App\Repositories\PaymentAccountsRepositories;
use App\Repositories\PaymentServiceProvidersRepositories;
use Illuminate\Support\Facades\Log;

class PaymentAccountsService
{
    protected $paymentAccountsRepositories;
    protected $paymentServiceProvidersRepositories;
    protected $dbConnectionService;

    public function __construct(
        PaymentAccountsRepositories $paymentAccountsRepositories,
        PaymentServiceProvidersRepositories $paymentServiceProvidersRepositories,
        DbConnectionService $dbConnectionService
    )
    {
        $this->paymentAccountsRepositories = $paymentAccountsRepositories;
        $this->paymentServiceProvidersRepositories = $paymentServiceProvidersRepositories;
        $this->paymentAccountsRepositories->setConnection($dbConnectionService->getConnection());
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

    public function fetchList($params)
    {
        $result = [];
        $payment_configurations = $this->paymentAccountsRepositories->findBy([
            'pc_project' => $params['client'],
            'pc_service' => $params['type']
        ]);
        foreach ($payment_configurations->toArray() as $payment_config) {
            $country = $payment_config['pc_country'];
            $city    = $payment_config['pc_city'];
            $res_key = $country . '-' . $city;
            $account = $this->paymentAccountsRepositories->fetch(['pa_id' => $payment_config['pc_xref_pa_id']]);
            if (isset($result[$res_key])) {
                $result[$res_key]['service'] = $result[$res_key]['service'] . ', ' . $account->pa_name;
            } else {
                $payment = [
                    'country' => $country,
                    'city'    => $city,
                    'service' => $account->pa_name
                ];
                $result[$res_key] = $payment;
            }
        }
        return $result;
    }

    public function paymentAccount($params)
    {
        $all_payment_config = $this->PaymentAccountsRepositories->fetchSelect()->toArray();
        $exist_payment_config = $this->PaymentConfigurationsService->getExistsConfigs($params['pc_id']);
        $res = array_filter($all_payment_config, function ($v, $k) use ($exist_payment_config) {
            foreach ($exist_payment_config as $key => $val) {
                if ($val['pa_name'] . $val['pa_type'] == $v['pa_name'] . $v['pa_type']) {
                    return false;
                }
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);
        $payment_config = array_values($res);
        foreach ($payment_config as $k => $v) {
            $payment_config[$k]['pa_name_type'] = $v['pa_name'] . ' (' . $v['pa_type'] . ')';
        }
        return $payment_config;
    }

}
