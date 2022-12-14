<?php

namespace App\Services;

use App\Repositories\refundItemRepository;
use App\Repositories\TransactionRepository;

class RefundItemsService
{
    /**
     * @var refundItemRepository
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
        $transactionItems = $this->transactionItemsService->fetch($attributes)
            ->groupBy('ti_xref_transaction_id')->toArray();

        if (empty($transactionItems)) {
            return [];
        }
        $where = collect([
            ['transaction_items.ti_xref_f_id', '=', $attributes['ti_xref_f_id']],
        ]);

        $refundItems = $this->refundItemRepository->fetchRefundItems($where->toArray())->toArray();
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
                'ri_amount' => (float) $rItem['ri_amount'],
                'ri_reason_type' => $rItem['ri_reason_type'],
                'ri_status' => $rItem['ri_status'],
                'ri_invoice_path' => $rItem['ri_invoice_path'],
            ];
            $refundRequestArray[$rItem['r_id']] = [
                'r_id' => $rItem['r_id'],
                'r_issuer' => $rItem['r_issuer'],
                'r_reason_type' => $rItem['r_reason_type'],
                'r_status' => $rItem['r_status'],
                'r_appointment_date' => $rItem['r_appointment_date'],
                'r_items' => array_values($refundItemsArray[$rItem['r_id']]),
            ];
        }

        return array_values($refundRequestArray);
    }

    /**
     * @param array $transactions
     *
     * @return array
     */
    private function getTransactionItems(array $transactions): array
    {
        $transactionData = [];
        foreach ($transactions as $transactionId => $services) {
            $transaction = $this->transactionRepository->fetch([
                't_transaction_id' => $transactionId,
                't_status' => 'done',
            ])->first();
            if (empty($transaction)) {
                break;
            }
            $items['f_id'] = current($services)['ti_xref_f_id'];
            $items['skus'] = [];
            foreach ($services as $service) {
                $items['skus'][] = [
                    'ti_id' => $service['ti_id'],
                    'price_rule' => $service['ti_price_rule'],
                    'sku' => $service['ti_fee_type'],
                    'price' => $service['ti_amount'],
                    'vat' => $service['ti_vat'],
                    'quantity' => $service['ti_quantity'],
                    'amount_gross' => ($service['ti_vat'] / 100 * $service['ti_amount']) + $service['ti_amount'],
                ];
            }
            $transactionData[] = [
                't_id' => $transaction->t_id,
                'gateway' => $transaction->t_gateway,
                'agent_gateway' => $transaction->t_payment_method,
                'transaction_id' => $transactionId,
                'gateway_transaction_id' => $transaction->t_gateway_transaction_id,
                'currency' => $transaction->t_currency,
                'status' => 'done',
                'service' => $transaction->t_service,
                'tech_creation' => $transaction->t_tech_creation,
                'tech_modification' => $transaction->t_tech_modification,
                'items' => $items,
            ];
        }

        return $transactionData;
    }
}
