<?php

namespace App\Services\V2;

use App\Repositories\V2\TransactionItemsRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

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

    public function getItemsPreparedToSync(): array
    {
        return $this->getAll()
            ->map(function ($transactionItem) {
                return [
                    'f_id' => $transactionItem->ti_xref_f_id,
                    'sku' => $transactionItem->ti_fee_type,
                    'price' => $transactionItem->ti_amount,
                    'vat' => $transactionItem->ti_vat,
                    'quantity' => $transactionItem->ti_quantity,
                    'price_rule' => $transactionItem->ti_price_rule,
                    'product_name' => $transactionItem->ti_fee_name,
                    'label' => $transactionItem->ti_label,
                    'tag' => $transactionItem->ti_tag,
                ];
            })
            ->groupBy('f_id')
            ->transform(function ($items, $formId) {
                return [
                    'f_id' => $formId,
                    'skus' => $items->map(function ($item) {
                        return Arr::except($item, ['f_id']);
                    })->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Retreives all transaction items by transaction_id.
     *
     * @param string $transactionId
     *
     * @return null|\Illuminate\Database\Eloquent\Collection
     */
    private function getAll(): ?Collection
    {
        return TransactionItemsRepository::getAvailableByTransactionId($this->transactionId);
    }
}
