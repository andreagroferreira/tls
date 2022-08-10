<?php


namespace App\Repositories;

use App\Models\PaymentConfigurations;
use Illuminate\Support\Facades\DB;

class PaymentConfigurationsRepositories
{
    protected $paymentConfigurations;

    public function __construct(PaymentConfigurations $paymentConfigurations)
    {
        $this->paymentConfigurations = $paymentConfigurations;
    }

    public function setConnection($connection)
    {
        $this->paymentConfigurations->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->paymentConfigurations->getConnectionName();
    }

    public function fetch($where, $field = '*')
    {
        return $this->paymentConfigurations
            ->select($field)
            ->where($where)
            ->first();
    }

    public function create($attributes)
    {
        return $this->paymentConfigurations->create($attributes);
    }

    public function update($where, $attributes)
    {

        $paymentAccounts = $this->paymentConfigurations->where($where)->first();
        if (blank($paymentAccounts)) {
            return false;
        }

        foreach ($attributes as $key => $value) {
            $paymentAccounts->$key = $value;
        }
        $paymentAccounts->save();

        return $this->paymentConfigurations->find($paymentAccounts->pa_id);
    }

    public function findBy($attributes) {
        $result = $this->paymentConfigurations;
        foreach ($attributes as $key => $value) {
            $result = $result->where($key, '=', $value);
        }
        return $result->get();
    }
}
