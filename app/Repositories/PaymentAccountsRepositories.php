<?php


namespace App\Repositories;

use App\Models\PaymentAccounts;
use Illuminate\Support\Facades\DB;

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
            $paymentAccounts->$key = $value;
        }
        $paymentAccounts->save();

        return $this->paymentAccounts->find($paymentAccounts->pa_id);
    }

    public function findBy($attributes) {
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
}
