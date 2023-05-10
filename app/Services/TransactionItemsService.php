<?php

namespace App\Services;

use App\Repositories\TransactionItemsRepository;

class TransactionItemsService
{
    protected $transactionItemsRepository;
    protected $dbConnectionService;

    public function __construct(TransactionItemsRepository $transactionItemsRepository, DbConnectionService $dbConnectionService)
    {
        $this->transactionItemsRepository = $transactionItemsRepository;
        $this->dbConnectionService = $dbConnectionService;
        $this->transactionItemsRepository->setConnection($this->dbConnectionService->getConnection());
    }

    public function createMany($attributes)
    {
        return $this->transactionItemsRepository->createMany($attributes);
    }

    public function fetch($where, $field = '*')
    {
        return $this->transactionItemsRepository->fetch($where, $field);
    }

    public function fetchItemsByTransactionId($transaction_id)
    {
        $items = $this->transactionItemsRepository->fetch(
            ['ti_xref_transaction_id' => $transaction_id, 'ti_tech_deleted' => false],
            [
                'ti_xref_f_id AS f_id',
                'ti_fee_type AS sku',
                'ti_amount AS price',
                'ti_vat AS vat',
                'ti_quantity AS quantity',
                'ti_price_rule AS price_rule',
                'ti_fee_name AS product_name',
                'ti_label AS label',
                'ti_tag AS tag',
                'ti_xref_f_cai AS customer_reference',
            ]
        );

        if ($items->isEmpty()) {
            return [];
        }

        return $items->groupBy('f_id')
            ->transform(function ($item, $key) {
                $skus = [];
                foreach ($item as $value) {
                    $skus[] = collect($value)->only(
                        ['sku', 'price', 'vat', 'quantity', 'price_rule', 'product_name', 'label', 'tag', 'customer_reference']
                    )
                        ->toArray();
                }

                return ['f_id' => $key, 'skus' => $skus];
            })
            ->values();
    }

    public function fetchByTransactionId($transaction_id)
    {
        return $this->transactionItemsRepository->findBy([
            'ti_xref_transaction_id' => $transaction_id,
            'ti_tech_deleted' => false,
        ])->first();
    }

    /**
     * @param int $transactionItemId
     *
     * @return object
     */
    public function fetchByTransactionItemId(int $transactionItemId): object
    {
        return $this->transactionItemsRepository->fetchByTransactionItemId([
            'ti_id' => $transactionItemId,
            'ti_tech_deleted' => false,
        ])->first();
    }

    /**
     * @param string $transaction_id
     * @param array  $attributes
     *
     * @return mixed
     */
    public function update(string $transaction_id, array $attributes)
    {
        return $this->transactionItemsRepository->update($transaction_id, $attributes);
    }
}
