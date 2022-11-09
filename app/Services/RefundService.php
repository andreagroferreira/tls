<?php

namespace App\Services;

use App\Repositories\RefundCommsRepository;
use App\Repositories\RefundItemsRepository;
use App\Repositories\RefundRequestsRepository;
use Illuminate\Support\Facades\DB;

class RefundService
{
    protected $refundRequestsRepository;
    protected $refundItemsRepository;
    protected $refundCommsRepository;
    protected $dbConnectionService;

    public function __construct(
        RefundRequestsRepository $refundRequestsRepository,
        RefundItemsRepository $refundItemsRepository,
        RefundCommsRepository $refundCommsRepository,
        DbConnectionService $dbConnectionService
    ) {
        $this->refundRequestsRepository = $refundRequestsRepository;
        $this->refundItemsRepository = $refundItemsRepository;
        $this->refundCommsRepository = $refundCommsRepository;
        $this->dbConnectionService = $dbConnectionService;
        $this->refundRequestsRepository->setConnection($this->dbConnectionService->getConnection());
        $this->refundItemsRepository->setConnection($this->dbConnectionService->getConnection());
        $this->refundCommsRepository->setConnection($this->dbConnectionService->getConnection());
    }

    public function create(array $attributes)
    {
        $db_connection = DB::connection($this->dbConnectionService->getConnection());
        $db_connection->beginTransaction();

        try {
            $refundRequest = $this->refundRequestsRepository->create($attributes);
            $this->refundItemsRepository->createMany($refundRequest->rr_id, $attributes['items']);

            $db_connection->commit();
        } catch (\Exception $e) {
            $db_connection->rollBack();

            return false;
        }

        return ['t_id' => $refundRequest->rr_id];
    }
}
