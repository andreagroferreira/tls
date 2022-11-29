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
     * @var TransactionItemsService
     */
    protected $transactionService;

    /**
     * @param RefundRepository     $refundRepository
     * @param refundItemRepository $refundItemRepository
     * @param RefundLogRepository  $refundLogRepository
     * @param DbConnectionService  $dbConnectionService
     * @param RefundItemsService   $refundItemsService
     * @param TransactionItemsService $transactionItemsService
     * @param TransactionService $transactionService
     */
    public function __construct(
        RefundRepository $refundRepository,
        RefundItemRepository $refundItemRepository,
        RefundLogRepository $refundLogRepository,
        DbConnectionService $dbConnectionService,
        RefundItemsService $refundItemsService,
        TransactionItemsService $transactionItemsService,
        TransactionService $transactionService
    ) {
        $this->refundRepository = $refundRepository;
        $this->refundItemRepository = $refundItemRepository;
        $this->refundLogRepository = $refundLogRepository;
        $this->dbConnectionService = $dbConnectionService;
        $this->refundItemsService = $refundItemsService;
        $this->transactionItemsService = $transactionItemsService;
        $this->transactionService = $transactionService;
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
        $refundData = $this->refundRepository->fetch(['r_id' => $attributes['r_id']])->toArray();
        if (empty($refundData)) {
            return [];
        }
        $refundRequest = current($refundData);

        $refundItems = $this->refundItemRepository
            ->fetch(['ri_xref_r_id' => $attributes['r_id']])
            ->groupBy('ri_xref_ti_id')
            ->toArray();

        $ti_id = array_first($refundItems)[0]['ri_xref_ti_id'];
        $transaction = $this->transactionItemsService->fetch(['ti_id' => $ti_id], 'ti_xref_transaction_id')->first()->toArray();
        $refundRequest['transaction'] = $this->getTransactionItemsWithRefund($transaction['ti_xref_transaction_id'], $refundItems);

        return $refundRequest;
    }
    
    /**
     * @param  string $transaction_id
     * @param  array  $refundItems
     * 
     * @return array
     */
    private function getTransactionItemsWithRefund(string $transaction_id, array $refundItems): array
    {
        $fields = [
            't_id',
            't_transaction_id',
            't_xref_fg_id',
            't_client',
            't_issuer',
            't_gateway',
            't_gateway_transaction_id',
            't_currency',
            't_status',
            't_service',
            't_tech_creation',
            't_tech_modification'
        ];
        $transaction = $this->transactionService->fetchByWhere(['t_transaction_id' => $transaction_id], $fields)->first()->toArray();

        $transactionItems = $this->transactionItemsService
            ->fetch(['ti_xref_transaction_id' => $transaction_id])
            ->groupBy('ti_xref_f_id')
            ->toArray();
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
        $transaction['items'] = $transactionItemsWithRefund;

        return $transaction;
    }
}
