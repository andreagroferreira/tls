<?php

namespace App\Services;

use App\Repositories\PaymentAccountsRepositories;

class PaymentAccountsService
{
    protected $PaymentConfigurationsService;
    protected $PaymentAccountsRepositories;

    public function __construct(
        PaymentConfigurationsService $PaymentConfigurationsService,
        PaymentAccountsRepositories $PaymentAccountsRepositories,
        DbConnectionService $dbConnectionService
    ) {
        $this->PaymentConfigurationsService = $PaymentConfigurationsService;
        $this->PaymentAccountsRepositories = $PaymentAccountsRepositories;
        $this->PaymentAccountsRepositories->setConnection($dbConnectionService->getConnection());
    }

    public function create($params) {
        return $this->PaymentAccountsRepositories->insert($params);
    }

    public function fetch() {
        $select = ['pa_id', 'pa_name', 'pa_type'];
        return $this->PaymentAccountsRepositories->fetchSelect($select);
    }

    public function fetchById($id) {
        return $this->PaymentAccountsRepositories->fetchById($id);
    }

    public function update($params): object
    {
        return $this->PaymentAccountsRepositories->update(['pa_id' => $params['pa_id']], $params);
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
