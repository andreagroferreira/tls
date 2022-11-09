<?php

namespace App\Repositories;

use App\Models\RefundRequest;

class RefundRequestsRepository
{
    protected $refundRequestModel;

    public function __construct(RefundRequest $refundRequestModel)
    {
        $this->refundRequestModel = $refundRequestModel;
    }

    public function setConnection($connection)
    {
        $this->refundRequestModel->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->refundRequestModel->getConnectionName();
    }

    public function create($attributes)
    {
        return $this->refundRequestModel->create($attributes);
    }
}
