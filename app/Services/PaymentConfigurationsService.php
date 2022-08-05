<?php


namespace App\Services;

use App\Repositories\PaymentAccountsRepositories;
use Illuminate\Support\Facades\Log;

class PaymentConfigurationsService
{
    protected $paymentAccountsRepositories;

    public function __construct(
        PaymentAccountsRepositories $paymentAccountsRepositories
    )
    {
        $this->paymentAccountsRepositories = $paymentAccountsRepositories;
    }

    public function update($params): object
    {
        return $this->paymentAccountsRepositories->update(['pa_id' => $params['pa_id']], $params);
    }
}
