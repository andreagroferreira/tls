<?php

namespace App\Services;

use App\Repositories\PaymentAccountsRepositories;
use App\Repositories\PaymentConfigurationsRepositories;

class PaymentConfigurationsService
{
    protected $paymentAccountsRepositories;
    protected $paymentConfigurationsRepositories;
    protected $gatewayService;
    protected $dbConnectionService;

    public function __construct(
        PaymentAccountsRepositories $paymentAccountsRepositories,
        PaymentConfigurationsRepositories $paymentConfigurationsRepositories,
        GatewayService $gatewayService,
        DbConnectionService $dbConnectionService
    ) {
        $this->paymentAccountsRepositories = $paymentAccountsRepositories;
        $this->paymentConfigurationsRepositories = $paymentConfigurationsRepositories;
        $this->gatewayService = $gatewayService;
        $this->paymentAccountsRepositories->setConnection($dbConnectionService->getConnection());
        $this->paymentConfigurationsRepositories->setConnection($dbConnectionService->getConnection());
    }

    public function create($params): array
    {
        $payment_config = $this->paymentConfigurationsRepositories->fetch($params);
        if (empty($payment_config)) {
            $response = $this->paymentConfigurationsRepositories->create($params);
            $status = 'success';
            $message = $response;
        } else {
            $status = 'error';
            $message = 'The current data already exists in the database.';
        }

        return [
            'status' => $status,
            'message' => $message,
        ];
    }

    public function save($params)
    {
        $where = [
            'pc_xref_pa_id' => $params['pc_xref_pa_id'],
            'pc_project' => $params['pc_project'],
            'pc_country' => $params['pc_country'],
            'pc_city' => $params['pc_city'],
            'pc_service' => $params['pc_service'],
        ];

        return $this->paymentConfigurationsRepositories->update($where, $params);
    }

    public function fetch($id)
    {
        $res = $this->fetchById($id);
        $pc_project = $res['pc_project'];
        $pc_country = $res['pc_country'];
        $pc_city = $res['pc_city'];
        $pc_service = $res['pc_service'];
        $where = [
            'pc_project' => $pc_project,
            'pc_country' => $pc_country,
            'pc_city' => $pc_city,
            'pc_service' => $pc_service,
        ];

        return $this->paymentConfigurationsRepositories->fetchSelect($where);
    }

    public function fetchById($id)
    {
        return $this->paymentConfigurationsRepositories->fetchById($id);
    }

    public function fetchList($params)
    {
        $result = [];
        $payment_configurations = $this->paymentConfigurationsRepositories->findBy([
            'pc_project' => $params['client'],
            'pc_service' => $params['type'],
        ]);
        foreach ($payment_configurations->toArray() as $payment_config) {
            $country = $payment_config['pc_country'];
            $city = $payment_config['pc_city'];
            $res_key = $country.'-'.$city;
            $result[$res_key]['pc_id'] = $payment_config['pc_id'];
            $result[$res_key]['country'] = $country;
            $result[$res_key]['city'] = $city;
            if (!empty($payment_config['pc_xref_pa_id'])) {
                $account = $this->paymentAccountsRepositories->fetch(['pa_id' => $payment_config['pc_xref_pa_id']]);
                if ($account) {
                    $accountData = [
                        'pa_id' => $account->pa_id,
                        'pa_name' => $account->pa_name,
                        'pa_type' => $account->pa_type,
                    ];
                }
                if ($payment_config['pc_is_active']) {
                    $result[$res_key]['service'][] = $accountData;
                }
            } else {
                if (empty($result[$res_key]['service'])) {
                    $result[$res_key]['service'] = [];
                }
            }
        }

        return $result;
    }

    /**
     * @param string $city
     *
     * @return array
     */
    public function fetchPaymentGatewayTypes(string $city): array
    {
        $gateway = $this->gatewayService;
        $citiesInfo = config('list_city.'.$city);
        $result = [];

        if (empty($citiesInfo['gcc_xref_gc_id']) || empty($city)) {
            return [];
        }

        $clientInfo = explode('-', ENV('CLIENT'));
        $client = $clientInfo[1] ?? '';

        if (empty(ENV('CLIENT')) || sizeof($clientInfo) == 0 || $client == '') {
            return [];
        }

        $issuer = $citiesInfo['gcc_xref_gc_id'].$citiesInfo['gcc_id'].'2'.$client;

        if (sizeof($gateway->getGateways($client, $issuer, 'tls')) > 0) {
            $result[] = 'tls';
        }

        if (sizeof($gateway->getGateways($client, $issuer, 'gov')) > 0) {
            $result[] = 'gov';
        }

        return $result;
    }

    public function getExistsConfigs($pc_id)
    {
        $payment_configs = $this->fetch($pc_id);
        $paymentConfig = [];
        $payConfig = [];
        foreach ($payment_configs as $k => $v) {
            if ($v['pc_xref_pa_id']) {
                $res = $this->paymentAccountsRepositories->fetchById($v['pc_xref_pa_id']);
                if ($res['pa_id']) {
                    $paymentConfig['pa_id'] = $res['pa_id'];
                    $paymentConfig['pa_name'] = $res['pa_name'];
                    $paymentConfig['pa_type'] = $res['pa_type'];
                    $paymentConfig['is_show'] = $v['pc_is_active'];
                    $payConfig[] = $paymentConfig;
                }
            }
        }
        if ($payConfig) {
            $pa_name = array_column($payConfig, 'pa_name');
            array_multisort($pa_name, SORT_ASC, $payConfig);
        }

        return $payConfig;
    }

    public function paymentAccount($params)
    {
        $all_payment_config = $this->paymentAccountsRepositories->fetchSelect()->toArray();
        $exist_payment_config = $this->getExistsConfigs($params['pc_id']);
        $res = array_filter($all_payment_config, function ($v, $k) use ($exist_payment_config) {
            foreach ($exist_payment_config as $key => $val) {
                if ($val['pa_name'].$val['pa_type'] == $v['pa_name'].$v['pa_type']) {
                    return false;
                }
            }

            return true;
        }, ARRAY_FILTER_USE_BOTH);
        $payment_config = array_values($res);
        foreach ($payment_config as $k => $v) {
            $payment_config[$k]['pa_name_type'] = $v['pa_name'].' ('.ucfirst($v['pa_type']).')';
        }

        return $payment_config;
    }

    public function remove($params): array
    {
        $payment_config = $this->paymentConfigurationsRepositories->fetchById($params['pc_id']);
        if (!empty($payment_config)) {
            $response = $this->paymentConfigurationsRepositories->delete($params);
            $status = 'success';
            $message = $response;
        } else {
            $status = 'error';
            $message = 'The payment configuration does not exists in the database.';
        }

        return [
            'status' => $status,
            'message' => $message,
        ];
    }
}
