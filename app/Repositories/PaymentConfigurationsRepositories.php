<?php


namespace App\Repositories;

use App\Models\PaymentConfigurations;
use Illuminate\Support\Facades\DB;

class PaymentConfigurationsRepositories
{
    protected $PaymentConfigurations;

    public function __construct(PaymentConfigurations $PaymentConfigurations)
    {
        $this->PaymentConfigurations = $PaymentConfigurations;
    }

    public function setConnection($connection)
    {
        $this->PaymentConfigurations->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->PaymentConfigurations->getConnectionName();
    }

    public function fetch($where, $field = '*')
    {
        return $this->PaymentConfigurations
            ->select($field)
            ->where($where)
            ->get();
    }

    public function create($attributes)
    {
        return $this->PaymentConfigurations->create($attributes);
    }

    public function update($where, $attributes)
    {

        $PaymentConfigurations = $this->PaymentConfigurations->where($where)->first();
        if (blank($PaymentConfigurations)) {
            return false;
        }

        foreach ($attributes as $key => $value) {
            $PaymentConfigurations->$key = $value;
        }
        $PaymentConfigurations->save();

        return $this->PaymentConfigurations->find($PaymentConfigurations->pa_id);
    }

    public function findBy($attributes) {
        $result = $this->PaymentConfigurations;
        foreach ($attributes as $key => $value) {
            $result = $result->where($key, '=', $value);
        }
        return $result->get();
    }

    public function fetchById($id)
    {
        return $this->PaymentConfigurations->find($id);
    }
}
