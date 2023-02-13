<?php

namespace App\Services\V2;

use App\Repositories\V2\TransactionItemsRepository;

class TransactionItemService
{
    /**
     * Retreives a single transaction by transaction_id.
     *
     * @param string $transactionId
     *
     * @return null|\Illuminate\Database\Eloquent\Collection
     */
    public function getAllByTransactionId(string $transactionId): ?\Illuminate\Database\Eloquent\Collection
    {
        return TransactionItemsRepository::getAllByTransactionId($transactionId);
    }
}
