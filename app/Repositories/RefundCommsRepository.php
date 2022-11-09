<?php

namespace App\Repositories;

use App\Models\RefundComms;

class RefundCommsRepository
{
    protected $refundCommsModel;

    public function __construct(RefundComms $refundCommsModel)
    {
        $this->refundCommsModel = $refundCommsModel;
    }

    public function setConnection($connection)
    {
        $this->refundCommsModel->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->refundCommsModel->getConnectionName();
    }

    public function fetch($where, $field = '*')
    {
        return $this->refundCommsModel
            ->select($field)
            ->where($where)
            ->get();
    }
}
