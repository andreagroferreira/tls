<?php

namespace App\Repositories;

use App\Models\RefundComm;

class RefundCommsRepository
{
    protected $refundCommModel;

    public function __construct(RefundComm $refundCommModel)
    {
        $this->refundCommModel = $refundCommModel;
    }

    public function setConnection($connection)
    {
        $this->refundCommModel->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->refundCommModel->getConnectionName();
    }

    public function fetch($where, $field = '*')
    {
        return $this->refundCommModel
            ->select($field)
            ->where($where)
            ->get();
    }
}
