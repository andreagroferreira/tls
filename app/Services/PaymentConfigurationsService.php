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
        PaymentAccountsRepositories $paymentAccountsRepositories,
        PaymentConfigurationsRepositories $paymentConfigurationsRepositories,
        DbConnectionService $dbConnectionService
    )
    {
        $this->paymentAccountsRepositories = $paymentAccountsRepositories;
        $this->paymentConfigurationsRepositories = $paymentConfigurationsRepositories;
        $this->paymentAccountsRepositories->setConnection($dbConnectionService->getConnection());
        $this->paymentConfigurationsRepositories->setConnection($dbConnectionService->getConnection());
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
}
