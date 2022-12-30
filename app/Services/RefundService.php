<?php

namespace App\Services;

use App\Repositories\RefundItemRepository;
use App\Repositories\RefundLogRepository;
use App\Repositories\RefundRepository;
use App\Traits\FeatureVersionsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundService
{
    use FeatureVersionsTrait;

    /**
     * @var RefundRepository
     */
    protected $refundRepository;

    /**
     * @var refundItemRepository
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
     * @param RefundRepository        $refundRepository
     * @param refundItemRepository    $refundItemRepository
     * @param RefundLogRepository     $refundLogRepository
     * @param DbConnectionService     $dbConnectionService
     * @param RefundItemsService      $refundItemsService
     * @param TransactionItemsService $transactionItemsService
     * @param TransactionService      $transactionService
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
     * @return array
     */
    public function create(array $attributes): array
    {
        $refundData = [
            'r_issuer' => $attributes['r_issuer'],
            'r_reason_type' => $attributes['reason'] ?? '',
            'r_status' => 'done',
            'r_appointment_date' => $attributes['appointment_date'] ?? '',
        ];
        $dbConnection = DB::connection($this->dbConnectionService->getConnection());
        $dbConnection->beginTransaction();

        try {
            $refundRequest = $this->refundRepository->create($refundData);
            $this->refundItemRepository->createMany(
                $this->convertItemsFieldToArray(
                    $refundRequest->r_id,
                    $attributes['items']
                )
            );
            $refundItemRequest = $this->refundItemRepository->fetchRefundItems(
                ['ri_xref_r_id' => $refundRequest->r_id]
            );

            $this->refundLogRepository->createMany(
                $this->createLogFieldArray(
                    'done',
                    'status_change',
                    $refundItemRequest,
                    $attributes['agent']
                )
            );
            $dbConnection->commit();
        } catch (\Exception $e) {
            $dbConnection->rollBack();

            return [];
        }

        if ($refundRequest->r_status === 'done') {
            if (!$this->isVersion(1, $attributes['r_issuer'], 'transaction_sync')) {
                $this->syncRefundTransactionToEcommerce($refundRequest, $refundItemRequest);
            }
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

        return $transactionsRefundItems;
    }

    /**
     * @param array $attributes
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

        $tiId = array_first($refundItems)[0]['ri_xref_ti_id'];
        $transactionItem = $this->transactionItemsService
            ->fetch(['ti_id' => $tiId], 'ti_xref_transaction_id')
            ->first()
            ->toArray();
        $refundRequest['transaction'] = $this->getTransactionItemsWithRefund(
            $transactionItem['ti_xref_transaction_id'],
            $refundItems
        );

        return $refundRequest;
    }

    /**
     * @param int $transactionItemId
     *
     * @return string
     */
    public function getIssuer(int $transactionItemId): string
    {
        return $this->transactionItemsService
            ->fetchByTransactionItemId($transactionItemId)->t_issuer ?? '';
    }

    /**
     * @param int $transactionItemId
     * @param int $transactionItemQuantity
     * @param int $quantity
     *
     * @return bool
     */
    public function getRefundItemStatus(int $transactionItemId, int $transactionItemQuantity, int $quantity): bool
    {
        $refundQuantityCount = $this->refundItemRepository
            ->fetchRefundItems(['ri_xref_ti_id' => $transactionItemId])
            ->sum('ri_quantity');
        if (($refundQuantityCount + $quantity) <= $transactionItemQuantity) {
            return true;
        }

        return false;
    }

    /**
     * @param object $refundRequest
     * @param object $refundItemRequest
     *
     * @return void
     */
    private function syncRefundTransactionToEcommerce(object $refundRequest, object $refundItemRequest): void
    {
        $transactionId = $refundItemRequest->first()->ti_xref_transaction_id;
        $refundItems = $this->refundItemRepository
            ->fetch(['ri_xref_r_id' => $refundRequest->r_id])
            ->groupBy('ri_xref_ti_id')
            ->toArray();
        $transaction = $this->getTransactionItemsWithRefund($transactionId, $refundItems);
        if ($transaction && !empty($transaction['t_items'])) {
            if (!empty($transaction['t_xref_fg_id'])) {
                $ecommerceSyncStatus = $this->transactionService->syncTransactionToEcommerce($transaction, 'REFUND');
                if (!empty($ecommerceSyncStatus['error_msg'])) {
                    $error_msg[] = $ecommerceSyncStatus['error_msg'];
                }
            }
        }
        if (!empty($error_msg)) {
            Log::error('Refund ERROR: Refund '.$refundRequest->r_id.' failed, because: '.json_encode($error_msg, 256));
        }
    }

    /**
     * @param string $transactionId
     * @param array  $refundItems
     *
     * @return array
     */
    private function getTransactionItemsWithRefund(string $transactionId, array $refundItems): array
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
            't_tech_modification',
        ];
        $transaction = $this->transactionService
            ->fetchByWhere(['t_transaction_id' => $transactionId], $fields)
            ->first()
            ->toArray();

        $transactionItems = $this->transactionItemsService
            ->fetch(['ti_xref_transaction_id' => $transactionId])
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
        $transaction['t_items'] = $transactionItemsWithRefund;

        return $transaction;
    }

    /**
     * @param int    $refundId
     * @param string $refundItems
     *
     * @return array
     */
    private function convertItemsFieldToArray(int $refundId, string $refundItems): array
    {
        $response = [];
        foreach (json_decode($refundItems, true) as $items) {
            $res = [
                'ri_xref_r_id' => $refundId,
                'ri_xref_ti_id' => $items['ti_id'],
                'ri_quantity' => $items['quantity'],
                'ri_amount' => $items['amount'],
                'ri_reason_type' => 'other',
                'ri_status' => 'done',
            ];
            $response[] = $res;
        }

        return $response;
    }

    /**
     * @param string $description
     * @param string $type
     * @param object $refundItems
     * @param string $agent
     *
     * @return array
     */
    private function createLogFieldArray(
        string $description,
        string $type,
        object $refundItems,
        string $agent
    ): array {
        $response = [];
        foreach ($refundItems as $items) {
            $res = [
                'rl_xref_r_id' => $items['ri_xref_r_id'],
                'rl_xref_ri_id' => $items['ri_id'],
                'rl_type' => $type,
                'rl_description' => $description,
                'rl_agent' => $agent,
            ];
            $response[] = $res;
        }

        return $response;
    }
}
