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

    public function fetchAll($where, $field = '*')
    {
        return $this->paymentConfigurations
            ->select($field)
            ->where($where)
            ->get();
    }

    public function create($attributes)
    {
        return $this->paymentConfigurations->create($attributes);
    }

    public function update($where, $attributes)
    {
        $PaymentConfigurationsInfo = $this->paymentConfigurations->where($where)->first();
        $pc_xref_pa_id = $where['pc_xref_pa_id'];
        if (blank($PaymentConfigurationsInfo)) {
            $where['pc_xref_pa_id'] = '';
            $PaymentConfigurationsInfo = $this->paymentConfigurations->where($where)->first();
            if (blank($PaymentConfigurationsInfo)) {
                $where['pc_xref_pa_id'] = $pc_xref_pa_id;
                return $this->create($attributes);
            }
        }

        foreach ($attributes as $key => $value) {
            $PaymentConfigurationsInfo->$key = $value;
        }
        return $PaymentConfigurationsInfo->save();
    }

    public function findBy($attributes) {
        $result = $this->paymentConfigurations;
        foreach ($attributes as $key => $value) {
            $result = $result->where($key, '=', $value);
        }
        return $result->get();
    }

    public function fetchById($id)
    {
        return $this->paymentConfigurations->find($id);
    }


}
