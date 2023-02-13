<?php

namespace App\Repositories\V2;

use App\Models\Transactions;

class TransactionRepository
{
    /**
     * Retreives a single transaction by transaction_id.
     *
     * @param int $transactionId
     *
     * @return null|\App\Models\Transactions
     */
    public function getById(int $transactionId): ?Transactions
    {
        return Transactions::where('t_id', $transactionId)->first();
    }
}
