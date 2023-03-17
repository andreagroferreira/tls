<?php

namespace App\Repositories\V2;

use App\Models\TransactionItems;
use Illuminate\Database\Eloquent\Collection;

class TransactionItemsRepository
{
    /**
     * Retreives all not deleted transaction items by transaction id.
     *
     * @param string $transactionId
     *
     * @return null|Collection
     */
    public function getAvailableByTransactionId(string $transactionId): ?Collection
    {
        return TransactionItems::where('ti_xref_transaction_id', $transactionId)
            ->where('ti_tech_deleted', false)
            ->get();
    }
}
