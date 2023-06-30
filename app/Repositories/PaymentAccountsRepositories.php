<?php

namespace App\Repositories;

use App\Models\PaymentAccounts;

class PaymentAccountsRepositories
{
    protected $paymentAccounts;

    public function __construct(PaymentAccounts $paymentAccounts)
    {
        $this->paymentAccounts = $paymentAccounts;
    }

    public function setConnection($connection)
    {
        $this->paymentAccounts->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->paymentAccounts->getConnectionName();
    }

    public function fetch($where, $field = '*')
    {
        return $this->paymentAccounts
            ->select($field)
            ->where($where)
            ->first();
    }

    public function fetchSelect($field = '*')
    {
        return $this->paymentAccounts
            ->select($field)
            ->orderByDesc('pa_id')
            ->get();
    }

    public function create($attributes)
    {
        return $this->paymentAccounts->create($attributes);
    }

    public function update($where, $attributes)
    {
        $paymentAccounts = $this->paymentAccounts->where($where)->first();
        if (blank($paymentAccounts)) {
            return false;
        }

        foreach ($attributes as $key => $value) {
            $paymentAccounts->{$key} = $value;
        }
        $paymentAccounts->save();

        return $this->paymentAccounts->find($paymentAccounts->pa_id);
    }

    public function findBy($attributes)
    {
        $result = $this->paymentAccounts;
        foreach ($attributes as $key => $value) {
            $result = $result->where($key, '=', $value);
        }

        return $result->get();
    }

    public function fetchById($id)
    {
        return $this->paymentAccounts->find($id);
    }

    public function fetchByIdAndPspId($ids, $psp_id)
    {
        return $this->paymentAccounts
            ->whereIn('pa_id', $ids)
            ->where('pa_xref_psp_id', $psp_id)
            ->get();
    }

    /**
     * @param string $gateway
     * @param string $client
     * @param string $country
     * @param string $city
     * @param string $service
     *
     * @return null|PaymentAccounts
     */
    public function findByPspCodeLocationAndService(
        string $gateway,
        string $client,
        string $country,
        string $city,
        string $service
    ): ?PaymentAccounts {
        $env = env('APP_ENV') === 'production' ? 'production' : 'sandbox';

        return $this->paymentAccounts
            ->select('payment_accounts.*')
            ->join('payment_configurations', 'payment_configurations.pc_xref_pa_id', '=', 'payment_accounts.pa_id')
            ->join('payment_service_providers', 'payment_service_providers.psp_id', '=', 'payment_accounts.pa_xref_psp_id')
            ->where('psp_code', $gateway)
            ->where('pa_type', $env)
            ->where('pc_city', $city)
            ->where('pc_country', $country)
            ->where('pc_project', $client)
            ->where('pc_service', $service)
            ->where('pc_is_active', true)
            ->first();
    }
}
