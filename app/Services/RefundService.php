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
     * @param RefundRepository        $refundRepository
     * @param refundItemRepository    $refundItemRepository
     * @param RefundLogRepository     $refundLogRepository
     * @param DbConnectionService     $dbConnectionService
     * @param RefundItemsService      $refundItemsService
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
