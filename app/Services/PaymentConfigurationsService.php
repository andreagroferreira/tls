<?php


namespace App\Services;

use App\Repositories\PaymentAccountsRepositories;
use App\Repositories\PaymentConfigurationsRepositories;
use Illuminate\Support\Facades\Log;

class PaymentConfigurationsService
{
    protected $paymentAccountsRepositories;
    protected $paymentConfigurationsRepositories;

    public function __construct(
        PaymentAccountsRepositories $paymentAccountsRepositories,
        PaymentConfigurationsRepositories $paymentConfigurationsRepositories,
        DbConnectionService $dbConnectionService
    )
    {
        $this->paymentAccountsRepositories = $paymentAccountsRepositories;
        $this->paymentConfigurationsRepositories = $paymentConfigurationsRepositories;
        $this->paymentConfigurationsRepositories->setConnection($dbConnectionService->getConnection());
    }

    public function update($params): object
    {
        return $this->paymentAccountsRepositories->update(['pa_id' => $params['pa_id']], $params);
    }

    public function fetch($id) {
        $res = $this->fetchById($id);
        $pc_project = $res['pc_project'];
        $pc_country = $res['pc_country'];
        $pc_city = $res['pc_city'];
        $where = ['pc_project'=>$pc_project, 'pc_country'=>$pc_country, 'pc_city'=>$pc_city];
        return $this->paymentConfigurationsRepositories->fetch($where);
    }

    public function fetchById($id) {
        return $this->paymentConfigurationsRepositories->fetchById($id);
    }
}
