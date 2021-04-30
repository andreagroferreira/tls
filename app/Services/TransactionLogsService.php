<?php

namespace App\Services;

use App\Repositories\TransactionLogsRepository;
use App\Services\DbConnectionService;

class TransactionLogsService
{
    protected $transactionLogsRepository;

    public function __construct(TransactionLogsRepository $transactionLogsRepository, DbConnectionService $dbConnectionService) {
        $this->transactionLogsRepository = $transactionLogsRepository;
        $this->transactionLogsRepository->setConnection($dbConnectionService->getConnection());
    }

    public function create($attributes) {
        return $this->transactionLogsRepository->insert($attributes);
    }
}
