<?php

namespace App\Repositories;

use App\Models\RefundItems;

class RefundItemsRepository
{
    protected $refundItemsModel;

    public function __construct(RefundItems $refundItemsModel)
    {
        $this->refundItemsModel = $refundItemsModel;
    }

    public function setConnection($connection)
    {
        $this->refundItemsModel->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->refundItemsModel->getConnectionName();
    }

    public function createMany($attributes)
    {
        return $this->refundItemsModel->insert($attributes);
    }
}
