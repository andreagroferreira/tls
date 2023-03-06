<?php

namespace App\Repositories;

use App\Models\TransferTableTransaction;
use App\Models\TransferTableTransactionItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransferTableTransactionRepository
{
    /**
     * @var TransferTableTransaction
     */
    protected $transferTableTransactionModel;

    /**
     * @var TransferTableTransactionItem
     */
    protected $transferTableTransactionsItemModel;

    /**
     * @param TransferTableTransaction     $transferTableTransactionModel
     * @param TransferTableTransactionItem $transferTableTransactionsItemModel
     */
    public function __construct(
        TransferTableTransaction $transferTableTransactionModel,
        TransferTableTransactionItem $transferTableTransactionsItemModel
    ) {
        $this->transferTableTransactionModel = $transferTableTransactionModel;
        $this->transferTableTransactionsItemModel = $transferTableTransactionsItemModel;
    }

    /**
     * @param mixed $connection
     *
     * @return void
     */
    public function setConnection($connection)
    {
        $this->transferTableTransactionModel->setConnection($connection);
        $this->transferTableTransactionsItemModel->setConnection($connection);
    }

    /**
     * @return string
     */
    public function getConnection(): ?string
    {
        return $this->transferTableTransactionModel->getConnectionName();
    }

    /**
     * @param array $transaction
     * @param array $transactionItems
     *
     * @return bool
     */
    public function insertTransactionAndTransactionItems(array $transaction, array $transactionItems): bool
    {
        $this->transferTableTransactionModel->create($transaction);
        $this->transferTableTransactionsItemModel->insert($transactionItems);

        return true;
    }

    /**
     * @param array $where
     * @param mixed $field
     *
     * @return Collection
     */
    public function fetch(array $where, $field = '*'): Collection
    {
        return $this->transferTableTransactionModel
            ->select($field)
            ->where($where)
            ->get();
    }

    /**
     * @param array $where
     * @param mixed $field
     *
     * @return Collection
     */
    public function fetchTransactionItems(array $where, $field = '*'): Collection
    {
        return $this->transferTableTransactionsItemModel
            ->select($field)
            ->where($where)
            ->get();
    }

    /**
     * @param array $where
     * @param array $attributes
     *
     * @return bool|Collection
     */
    public function update(array $where, array $attributes): ?Collection
    {
        $transaction = $this->transferTableTransactionModel->where($where)->first();
        if (blank($transaction)) {
            return false;
        }
        foreach ($attributes as $key => $value) {
            $transaction->{$key} = $value;
        }
        $transaction->save();

        return $this->transferTableTransactionModel->find($transaction->t_id);
    }

    /**
     * @return mixed
     */
    public function getTransactionIdSeq()
    {
        $res = DB::connection($this->getConnection())->select("SELECT nextval('transfer_table_transaction_t_id_seq')");

        return array_first($res)->nextval;
    }

    /**
     * @param array $attributes
     *
     * @return string
     */
    public function findBy(array $attributes): string
    {
        $result = $this->transferTableTransactionModel;
        foreach ($attributes as $key => $value) {
            $result = $result->where($key, '=', $value);
        }

        return $result->get();
    }
}
