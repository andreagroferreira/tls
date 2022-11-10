<?php

namespace App\Services;

use App\Repositories\refundItemRepository;
use App\Repositories\RefundLogRepository;
use App\Repositories\RefundRepository;
use Illuminate\Support\Facades\DB;

class RefundService
{
    protected $refundRepository;
    protected $refundItemRepository;
    protected $refundLogRepository;
    protected $dbConnectionService;

    public function __construct(
        RefundRepository $refundRepository,
        RefundItemRepository $refundItemRepository,
        RefundLogRepository $refundLogRepository,
        DbConnectionService $dbConnectionService
    ) {
        $this->refundRepository = $refundRepository;
        $this->refundItemRepository = $refundItemRepository;
        $this->refundLogRepository = $refundLogRepository;
        $this->dbConnectionService = $dbConnectionService;
        $this->refundRepository->setConnection($this->dbConnectionService->getConnection());
        $this->refundItemRepository->setConnection($this->dbConnectionService->getConnection());
        $this->refundLogRepository->setConnection($this->dbConnectionService->getConnection());
    }

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
}
