<?php


namespace App\Services;

use App\Repositories\PaymentAccountsRepositories;
use App\Repositories\PaymentConfigurationsRepositories;
use Illuminate\Support\Facades\Log;

class PaymentConfigurationsService
{
    protected $paymentAccountsRepositories;
    protected $paymentConfigurationsRepositories;
    protected $dbConnectionService;

    public function __construct(
        PaymentAccountsRepositories       $paymentAccountsRepositories,
        PaymentConfigurationsRepositories $paymentConfigurationsRepositories,
        DbConnectionService               $dbConnectionService
    )
    {
        $this->paymentAccountsRepositories = $paymentAccountsRepositories;
        $this->paymentConfigurationsRepositories = $paymentConfigurationsRepositories;
        $this->paymentAccountsRepositories->setConnection($dbConnectionService->getConnection());
        $this->paymentConfigurationsRepositories->setConnection($dbConnectionService->getConnection());
    }

    public function create($params) {
        return $this->paymentConfigurationsRepositories->create($params);
    }

    public function save($params)
    {
        $where = ['pc_xref_pa_id' => $params['pc_xref_pa_id'], 'pc_project' => $params['pc_project'], 'pc_country' => $params['pc_country'], 'pc_city' => $params['pc_city'], 'pc_service' => $params['pc_service']];
        return $this->paymentConfigurationsRepositories->update($where, $params);
    }

    public function fetch($id)
    {
        $res = $this->fetchById($id);
        $pc_project = $res['pc_project'];
        $pc_country = $res['pc_country'];
        $pc_city = $res['pc_city'];
        $pc_service = $res['pc_service'];
        $where = ['pc_project' => $pc_project, 'pc_country' => $pc_country, 'pc_city' => $pc_city, 'pc_service' => $pc_service];
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
            'pc_service' => $params['type']
        ]);
        foreach ($payment_configurations->toArray() as $payment_config) {
            $country = $payment_config['pc_country'];
            $city = $payment_config['pc_city'];
            $res_key = $country . '-' . $city;
            $account = $this->paymentAccountsRepositories->fetch(['pa_id' => $payment_config['pc_xref_pa_id']]);
            if ($account) {
                if (isset($result[$res_key])) {
                    $result[$res_key]['service'] = $result[$res_key]['service'] . ', ' . $account->pa_name;
                } else {
                    $payment = [
                        'pc_id' => $payment_config['pc_id'],
                        'country' => $country,
                        'city' => $city,
                        'service' => $account->pa_name
                    ];
                    $result[$res_key] = $payment;
                }
            }
        }
        return $result;
    }

    public function getExistsConfigs($pc_id)
    {
        $payment_configs = $this->fetch($pc_id);
        $paymentConfig = [];
        foreach ($payment_configs as $k => $v) {
            $res = $this->paymentAccountsRepositories->fetchById($v['pc_xref_pa_id']);
            if ($res['pa_id']) {
                $paymentConfig['pa_id'] = $res['pa_id'];
                $paymentConfig['pa_name'] = $res['pa_name'];
                $paymentConfig['pa_type'] = $res['pa_type'];
                $paymentConfig['is_show'] = $v['pc_is_actived'];
                $payConfig[] = $paymentConfig;
            }
        }
        return $payConfig;
    }
}