<?php

namespace App\Repositories\V2;

use App\Models\Transactions;

class TransactionRepository
{
    /**
     * Retreives a single transaction by t_id.
     *
     * @param int $id
     *
     * @return null|Transactions
     */
    public static function getById(int $id): ?Transactions
    {
        return Transactions::where('t_id', $id)->first();
    }

    /**
     * Retreives a single transaction by t_transaction_id.
     *
     * @param string $transactionId
     *
     * @return null|Transactions
     */
    public function getByTransactionId(string $transactionId): ?Transactions
    {
        return Transactions::where('t_transaction_id', $transactionId)->first();
    }
}
