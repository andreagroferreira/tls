<?php

namespace App\Repositories;

use App\Models\TransactionItems;

class TransactionItemsRepository
{
    protected $transactionItemsModel;

    public function __construct(TransactionItems $transactionItems)
    {
        $this->transactionItemsModel = $transactionItems;
    }

    public function setConnection($connection)
    {
        $this->transactionItemsModel->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->transactionItemsModel->getConnection();
    }

    public function createMany($attributes)
    {
        return $this->transactionItemsModel->insert($attributes);
    }

    public function fetch($where, $field = '*')
    {
        return $this->transactionItemsModel->select($field)->where($where)->get();
    }

    public function insert($attributes)
    {
        return $this->transactionItemsModel->newInstance()->create($attributes);
    }

    public function findBy($attributes)
    {
        $result = $this->transactionItemsModel;
        foreach ($attributes as $key => $value) {
            $result = $result->where($key, '=', $value);
        }

        return $result->get();
    }

    public function update($transaction_id, $attributes)
    {
        $result = $this->transactionItemsModel->where('ti_xref_transaction_id', '=', $transaction_id)->first();
        foreach ($attributes as $key => $value) {
            $result->{$key} = $value;
        }
        $result->save();

        return $result;
    }

    /**
     * @param array $where
     *
     * @return object
     */
    public function fetchByTransactionItemId(array $where): object
    {
        return $this->transactionItemsModel
            ->join('transactions', 'transactions.t_transaction_id', '=', 'transaction_items.ti_xref_transaction_id')
            ->where($where)
            ->select(['*'])
            ->get();
    }
}
