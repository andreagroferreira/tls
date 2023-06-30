<?php

namespace App\Services;

use App\Repositories\TransactionLogsRepository;

class TransactionLogsService
{
    protected $transactionLogsRepository;

    public function __construct(TransactionLogsRepository $transactionLogsRepository, DbConnectionService $dbConnectionService)
    {
        $this->transactionLogsRepository = $transactionLogsRepository;
        $this->transactionLogsRepository->setConnection($dbConnectionService->getConnection());
    }

    public function create($attributes)
    {
        return $this->transactionLogsRepository->insert($attributes);
    }

    /**
     * @param string $transactionId
     *
     * @return mixed
     */
    public function fetchByTransactionId(string $transactionId)
    {
        return $this->transactionLogsRepository->fetch(['tl_xref_transaction_id' => $transactionId])->first();
    }
}
