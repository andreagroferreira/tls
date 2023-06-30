<?php

namespace App\Repositories;

use App\Models\TransactionLogs as TransactionLogsModel;

class TransactionLogsRepository
{
    protected $transactionLogsModel;

    public function __construct(TransactionLogsModel $transactionLogsModel)
    {
        $this->transactionLogsModel = $transactionLogsModel;
    }

    public function setConnection($connection)
    {
        $this->transactionLogsModel->setConnection($connection);
    }

    public function insert($attributes)
    {
        return $this->transactionLogsModel->newInstance()->create($attributes);
    }

    public function fetch($where, $field = '*')
    {
        return $this->transactionLogsModel
            ->select($field)
            ->where($where)
            ->get();
    }
}
