<?php

namespace App\Services;

use App\Repositories\PaymentAccountsRepositories;

class PaymentAccountsService
{
    protected $PaymentAccountsRepositories;

    public function __construct(
        PaymentAccountsRepositories $PaymentAccountsRepositories,
        DbConnectionService $dbConnectionService
    ) {
        $this->PaymentAccountsRepositories = $PaymentAccountsRepositories;
        $this->PaymentAccountsRepositories->setConnection($dbConnectionService->getConnection());
    }

    public function create($params) {
        return $this->PaymentAccountsRepositories->insert($params);
    }

    public function fetch() {
        $select = ['pa_id', 'pa_name'];
        $pa_type = env('APP_ENV') === 'production' ? 'prod' : 'sandbox';
        $where = ['pa_type'=>$pa_type];
        return $this->PaymentAccountsRepositories->fetch($where,$select);
    }

    public function fetchById($id) {
        return $this->PaymentAccountsRepositories->fetchById($id);
    }

}
