<?php

namespace App\Services;

use App\Repositories\RefundItemsRepository;
use App\Repositories\RefundLogRepository;
use App\Repositories\RefundRepository;
use Illuminate\Support\Facades\DB;

class RefundService
{
    protected $refundRepository;
    protected $refundItemsRepository;
    protected $refundLogRepository;
    protected $dbConnectionService;

    public function __construct(
        RefundRepository $refundRepository,
        RefundItemsRepository $refundItemsRepository,
        RefundLogRepository $refundLogRepository,
        DbConnectionService $dbConnectionService
    ) {
        $this->refundRepository = $refundRepository;
        $this->refundItemsRepository = $refundItemsRepository;
        $this->refundLogRepository = $refundLogRepository;
        $this->dbConnectionService = $dbConnectionService;
        $this->refundRepository->setConnection($this->dbConnectionService->getConnection());
        $this->refundItemsRepository->setConnection($this->dbConnectionService->getConnection());
        $this->refundLogRepository->setConnection($this->dbConnectionService->getConnection());
    }

    public function create(array $attributes)
    {
        $db_connection = DB::connection($this->dbConnectionService->getConnection());
        $db_connection->beginTransaction();

        try {
            $refundRequest = $this->refundRepository->create($attributes);
            $this->refundItemsRepository->createMany($refundRequest->r_id, $attributes['items']);

            $db_connection->commit();
        } catch (\Exception $e) {
            $db_connection->rollBack();

            return false;
        }

        return ['t_id' => $refundRequest->rr_id];
    }
}
