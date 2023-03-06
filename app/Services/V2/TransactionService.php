<?php

namespace App\Services\V2;

use App\Models\Transactions;
use App\Repositories\V2\TransactionRepository;

class TransactionService
{
    /**
     * Retreives a single transaction by transaction_id.
     *
     * @param int $id
     *
     * @return null|Transactions
     */
    public function get(int $id): ?Transactions
    {
        return TransactionRepository::getById($id);
    }

    /**
     * Retreives a single transaction by transaction_id.
     *
     * @param string $transactionId
     *
     * @return null|Transactions
     */
    public function getByTransactionId(string $transactionId): ?Transactions
    {
        return TransactionRepository::getByTransactionId($transactionId);
    }
}
