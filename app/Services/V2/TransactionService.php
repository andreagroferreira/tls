<?php

namespace App\Services\V2;

use App\Repositories\V2\TransactionRepository;

class TransactionService
{
    /**
     * Retreives a single transaction by transaction_id.
     *
     * @param int $transactionId
     *
     * @return null|\App\Models\Transactions
     */
    public function get(int $transactionId): ?\App\Models\Transactions
    {
        return TransactionRepository::getById($transactionId);
    }
}
