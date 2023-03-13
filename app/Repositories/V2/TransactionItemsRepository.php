<?php

namespace App\Repositories\V2;

use App\Models\TransactionItems;
use Illuminate\Database\Eloquent\Collection;

class TransactionItemsRepository
{
    /**
     * Retreives a single transaction by transaction_id.
     *
     * @param string $transactionId
     *
     * @return null|Collection
     */
    public function getAllByTransactionId(string $transactionId): ?Collection
    {
        return TransactionItems::where('ti_xref_transaction_id', $transactionId)->get();
    }
}
