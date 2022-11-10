<?php

namespace App\Repositories;

use App\Models\RefundLog;

class RefundLogRepository
{
    /**
     * @var RefundLog
     */
    protected $refundLogModel;

    /**
     * @param RefundLog $refundLogModel
     */
    public function __construct(RefundLog $refundLogModel)
    {
        $this->refundLogModel = $refundLogModel;
    }

    public function setConnection($connection)
    {
        $this->refundLogModel->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->refundLogModel->getConnectionName();
    }

    public function fetch($where, $field = '*')
    {
        return $this->refundLogModel
            ->select($field)
            ->where($where)
            ->get();
    }
}
