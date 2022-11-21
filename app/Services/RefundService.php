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
     * @param RefundRepository     $refundRepository
     * @param refundItemRepository $refundItemRepository
     * @param RefundLogRepository  $refundLogRepository
     * @param DbConnectionService  $dbConnectionService
     * @param RefundItemsService   $refundItemsService
     */
    public function __construct(
        RefundRepository $refundRepository,
        RefundItemRepository $refundItemRepository,
        RefundLogRepository $refundLogRepository,
        DbConnectionService $dbConnectionService,
        RefundItemsService $refundItemsService
    ) {
        $this->refundRepository = $refundRepository;
        $this->refundItemRepository = $refundItemRepository;
        $this->refundLogRepository = $refundLogRepository;
        $this->dbConnectionService = $dbConnectionService;
        $this->refundItemsService = $refundItemsService;
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

        return ['t_id' => $refundRequest->rr_id];
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
}
