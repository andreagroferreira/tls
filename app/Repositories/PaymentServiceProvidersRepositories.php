<?php


namespace App\Repositories;

use App\Models\PaymentServiceProviders;
use Illuminate\Support\Facades\DB;

class PaymentServiceProvidersRepositories
{
    protected $paymentServiceProviders;

    public function __construct(PaymentServiceProviders $paymentServiceProviders)
    {
        $this->paymentServiceProviders = $paymentServiceProviders;
    }

    public function setConnection($connection)
    {
        $this->paymentServiceProviders->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->paymentServiceProviders->getConnectionName();
    }

    public function fetch($where, $field = '*')
    {
        return $this->paymentServiceProviders
            ->select($field)
            ->where($where)
            ->first();
    }

    public function fetchAll($field = '*')
    {
        return $this->paymentServiceProviders
            ->select($field)
            ->where(['psp_tech_deleted' => false])
            ->get();
    }

    public function create($attributes)
    {
        return $this->paymentServiceProviders->create($attributes);
    }

    public function update($where, $attributes)
    {

        $paymentAccounts = $this->paymentServiceProviders->where($where)->first();
        if (blank($paymentAccounts)) {
            return false;
        }

        foreach ($attributes as $key => $value) {
            $paymentAccounts->$key = $value;
        }
        $paymentAccounts->save();

        return $this->paymentServiceProviders->find($paymentAccounts->pa_id);
    }

    public function findBy($attributes) {
        $result = $this->paymentServiceProviders;
        foreach ($attributes as $key => $value) {
            $result = $result->where($key, '=', $value);
        }
        return $result->get();
    }
    
    /**
     * @param  string $fieldName
     * @param  array  $list
     * @param  array  $field
     * 
     * @return object
     */
    public function fetchIn(string $fieldName, array $list, array $field = ['*']): object
    {
        return $this->paymentServiceProviders
            ->select($field)
            ->whereIn($fieldName, $list)
            ->get();
    }
}
