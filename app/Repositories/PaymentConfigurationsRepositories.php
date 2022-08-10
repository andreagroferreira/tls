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
        $pc_xref_pa_id = $where['pc_xref_pa_id'];
        if (blank($PaymentConfigurations)) {
            $where['pc_xref_pa_id'] = '';
            $PaymentConfigurations = $this->PaymentConfigurations->where($where)->first();
            if (blank($PaymentConfigurations)) {
                $where['pc_xref_pa_id'] = $pc_xref_pa_id;
                return $this->create($attributes);
            }
        }

        foreach ($attributes as $key => $value) {
            $PaymentConfigurations->$key = $value;
        }
        return $PaymentConfigurations->save();
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

    public function delete($attributes)
    {
        return $this->PaymentConfigurations->where(['pc_project'=>$attributes['pc_project'], 'pc_country'=>$attributes['pc_country'], 'pc_city'=>$attributes['pc_city'], 'pc_service'=>$attributes['pc_service']])->delete();
    }
}
