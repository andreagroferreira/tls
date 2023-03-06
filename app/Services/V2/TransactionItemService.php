<?php

namespace App\Services\V2;

use App\Repositories\V2\TransactionItemsRepository;
use Illuminate\Database\Eloquent\Collection;

class TransactionItemService
{
    private $transactionId;

    public function __construct(string $transactionId)
    {
        $this->transactionId = $transactionId;
    }

    public function getAmount(): float
    {
        if ($transactionItems = $this->getAll()) {
            $amount = 0;

            foreach ($transactionItems as $item) {
                $amount += $item->ti_amount * ($item->ti_quantity ?? 1);
            }

            return $amount;
        }

        return 0;
    }

    public function getItems(): array
    {
        return $this->getAll()->toArray();
    }

    /**
     * Retreives a single transaction by transaction_id.
     *
     * @param string $transactionId
     *
     * @return null|\Illuminate\Database\Eloquent\Collection
     */
    private function getAll(): ?Collection
    {
        return TransactionItemsRepository::getAllByTransactionId($this->transactionId);
    }
}
