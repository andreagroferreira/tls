<?php

namespace App\Services;

use App\Repositories\RefundItemRepository;
use App\Repositories\TransactionRepository;

class RefundItemsService
{
    /**
     * @var RefundItemRepository
     */
    protected $refundItemRepository;

    /**
     * @var TransactionRepository
     */
    protected $transactionRepository;

    /**
     * @var DbConnectionService
     */
    protected $dbConnectionService;

    /**
     * @var TransactionItemsService
     */
    protected $transactionItemsService;

    public function __construct(
        RefundItemRepository $refundItemRepository,
        TransactionRepository $transactionRepository,
        DbConnectionService $dbConnectionService,
        TransactionItemsService $transactionItemsService
    ) {
        $this->refundItemRepository = $refundItemRepository;
        $this->transactionRepository = $transactionRepository;
        $this->dbConnectionService = $dbConnectionService;
        $this->transactionItemsService = $transactionItemsService;
        $this->refundItemRepository->setConnection($this->dbConnectionService->getConnection());
        $this->transactionRepository->setConnection($this->dbConnectionService->getConnection());
    }

    /**
     * @param array $attributes
     *
     * @return array
     */
    public function getRefundTransactionItems(array $attributes): array
    {
        $transactionItems = $this->transactionItemsService
            ->fetch($attributes)
            ->groupBy('ti_xref_transaction_id')
            ->toArray();

        if (empty($transactionItems)) {
            return [];
        }

        $refundItems = $this->refundItemRepository->fetchRefundItems([
            ['transaction_items.ti_xref_f_id', '=', $attributes['ti_xref_f_id']],
        ])->toArray();
        $transactions = $this->getTransactionItems($transactionItems);

        if (empty($transactions)) {
            return [];
        }

        return [
            'transaction' => $transactions,
            'refund_requests' => $this->getRefundRequestAndRefundItems($refundItems),
        ];
    }

    /**
     * @param array $refundItems
     *
     * @return array
     */
    private function getRefundRequestAndRefundItems(array $refundItems): array
    {
        $refundRequestArray = [];
        foreach ($refundItems as $rItem) {
            $refundItemsArray[$rItem['r_id']][$rItem['ri_id']] = [
                'ri_xref_ti_id' => $rItem['ri_xref_ti_id'],
                'ri_id' => $rItem['ri_id'],
                'ri_quantity' => $rItem['ri_quantity'],
                'ri_amount' => number_format((float) $rItem['ri_amount'], 2, '.', ''),
                'ri_reason_type' => $rItem['ri_reason_type'],
                'ri_status' => $rItem['ri_status'],
                'ri_invoice_path' => $rItem['ri_invoice_path'],
            ];
            $refundRequestArray[$rItem['r_id']] = [
                'r_id' => $rItem['r_id'],
                'r_issuer' => $rItem['r_issuer'],
                'r_reason_type' => $rItem['r_reason_type'],
                'r_status' => $rItem['r_status'],
                'r_items' => array_values($refundItemsArray[$rItem['r_id']]),
            ];
        }

        return array_values($refundRequestArray);
    }

    /**
     * @param array $transactionItems
     *
     * @return array
     */
    private function getTransactionItems(array $transactionItems): array
    {
        return $this->transactionRepository
            ->fetchDoneTransactionsByTransactionIds(array_keys($transactionItems))
            ->map(function ($transaction) use ($transactionItems) {
                return [
                    't_id' => $transaction->t_id,
                    'fg_id' => $transaction->t_xref_fg_id,
                    'gateway' => $transaction->t_gateway,
                    'agent_gateway' => $transaction->t_payment_method,
                    'transaction_id' => $transaction->t_transaction_id,
                    'gateway_transaction_id' => $transaction->t_gateway_transaction_id,
                    'currency' => $transaction->t_currency,
                    'status' => 'done',
                    'service' => $transaction->t_service,
                    'tech_creation' => $transaction->t_tech_creation,
                    'tech_modification' => $transaction->t_tech_modification,
                    'items' => $this->prepareTransactionItems($transactionItems[$transaction->t_transaction_id]),
                ];
            })->toArray();
    }

    /**
     * Prepares the transaction items for the response based on the services in the payload.
     *
     * @param array $services
     *
     * @return array
     */
    private function prepareTransactionItems(array $services): array
    {
        $transactionItems = [];
        foreach ($services as $service) {
            $transactionItems['f_id'] = $service['ti_xref_f_id'];
            $transactionItems['skus'][] = [
                'ti_id' => $service['ti_id'],
                'price_rule' => $service['ti_price_rule'],
                'sku' => $service['ti_fee_type'],
                'product_name' => $service['ti_fee_name'],
                'price' => number_format((float) $service['ti_amount'], 2, '.', ''),
                'vat' => $service['ti_vat'],
                'quantity' => $service['ti_quantity'],
                'price_without_tax' => number_format(
                    (float) $service['ti_amount'] - ($service['ti_vat'] / 100 * $service['ti_amount']),
                    2,
                    '.',
                    ''
                ),
            ];
        }

        return $transactionItems;
    }
}
