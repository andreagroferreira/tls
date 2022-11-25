<?php

namespace App\Services;

use App\Repositories\RefundItemRepository;
use App\Repositories\RefundLogRepository;
use App\Repositories\RefundRepository;
use Illuminate\Support\Facades\DB;

class RefundService
{
    /**
     * @var RefundRepository
     */
    protected $refundRepository;

    /**
     * @var refundItemRepository
     *
     */
    protected $refundItemRepository;

    /**
     * @var RefundLogRepository
     */
    protected $refundLogRepository;

    /**
     * @var DbConnectionService
     */
    protected $dbConnectionService;

    /**
     * @var RefundItemsService
     */
    protected $refundItemsService;

    /**
     * @var TransactionItemsService
     */
    protected $transactionItemsService;

    /**
     * @param RefundRepository     $refundRepository
     * @param refundItemRepository $refundItemRepository
     * @param RefundLogRepository  $refundLogRepository
     * @param DbConnectionService  $dbConnectionService
     * @param RefundItemsService   $refundItemsService
     * @param TransactionItemsService $transactionItemsService
     */
    public function __construct(
        RefundRepository $refundRepository,
        RefundItemRepository $refundItemRepository,
        RefundLogRepository $refundLogRepository,
        DbConnectionService $dbConnectionService,
        RefundItemsService $refundItemsService,
        TransactionItemsService $transactionItemsService
    ) {
        $this->refundRepository = $refundRepository;
        $this->refundItemRepository = $refundItemRepository;
        $this->refundLogRepository = $refundLogRepository;
        $this->dbConnectionService = $dbConnectionService;
        $this->refundItemsService = $refundItemsService;
        $this->transactionItemsService = $transactionItemsService;
        $this->refundRepository->setConnection($this->dbConnectionService->getConnection());
        $this->refundItemRepository->setConnection($this->dbConnectionService->getConnection());
        $this->refundLogRepository->setConnection($this->dbConnectionService->getConnection());
    }

    /**
     * @param array $attributes
     *
     * @return array|false
     */
    public function create(array $attributes)
    {
        $db_connection = DB::connection($this->dbConnectionService->getConnection());
        $db_connection->beginTransaction();

        try {
            $refundRequest = $this->refundRepository->create($attributes);
            $this->refundItemRepository->createMany($refundRequest->r_id, $attributes['items']);

            $db_connection->commit();
        } catch (\Exception $e) {
            $db_connection->rollBack();

            return false;
        }

        return ['r_id' => $refundRequest->r_id];
    }

    /**
     * @param array $attributes
     *
     * @return array
     */
    public function getRefundTransactionItems(array $attributes): array
    {
        $transactionsRefundItems = $this->refundItemsService->getRefundTransactionItems($attributes);
        if (empty($transactionsRefundItems)) {
            return [];
        }

        return collect($transactionsRefundItems)->sortBy('t_id', SORT_DESC)->values()->toArray();
    }

    /**
     * @param  array $attributes
     * 
     * @return array
     */
    public function getRefundRequest(array $attributes): array
    {
        $where = collect([
                            ['refund_items.ri_xref_r_id', '=', $attributes['r_id']],
                        ])->toArray();

        $refundData = $this->refundItemRepository->fetchRefundItems($where);
        if (empty($refundData)) {
            return [];
        }

        foreach ($refundData->first()->toArray() as $field => $value) {
            if (starts_with($field, 'r_')) {
                $refundRequest[$field] = $value;
            } elseif (starts_with($field, 't_')) {
                $transaction[$field] = $value;
            }
        }

        $transactionItems = $this->transactionItemsService
            ->fetch(['ti_xref_transaction_id' => $transaction['t_transaction_id']])
            ->groupBy('ti_xref_f_id')
            ->toArray();
        $transaction['items'] = $this->getTransactionItemsWithRefund($refundData->toArray(), $transactionItems);

        $refundRequest['transaction'] = $transaction;

        return $refundRequest;
    }
    
    /**
     * @param  array $refundData
     * @param  array $transactionItems
     * 
     * @return array
     */
    private function getTransactionItemsWithRefund(array $refundData, array $transactionItems): array
    {
        foreach ($refundData as $data) {
            foreach ($data as $field => $value) {
                if (starts_with($field, 'ri_') || starts_with($field, 'rl_')) {
                    if ($field !== 'ri_amount') {
                        $refundItems[$data['ti_id']][$field] = $value;
                    } else {
                        $refundItems[$data['ti_id']][$field] = floatval($value);
                    }
                }
            }
        }

        foreach ($transactionItems as $formId => $services) {
            $items['f_id'] = $formId;

            $items['skus'] = [];
            foreach ($services as $service) {
                $items['skus'][] = [
                    'ti_id' => $service['ti_id'],
                    'sku' => $service['ti_fee_type'],
                    'price' => $service['ti_amount'],
                    'vat' => $service['ti_vat'],
                    'quantity' => $service['ti_quantity'],
                    'amount_gross' => ($service['ti_vat'] / 100 * $service['ti_amount']) + $service['ti_amount'],
                    'refund_items' => $refundItems[$service['ti_id']] ?? [],
                ];
            }
            $transactionItemsWithRefund[] = $items;
        }

        return $transactionItemsWithRefund;
    }
}
