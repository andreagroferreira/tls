<?php

namespace App\Repositories;

use App\Models\RefundItem;

class RefundItemRepository
{
    protected $refundItemModel;

    public function __construct(RefundItem $refundItemModel)
    {
        $this->refundItemModel = $refundItemModel;
    }

    public function setConnection($connection)
    {
        $this->refundItemModel->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->refundItemModel->getConnectionName();
    }

    public function createMany($attributes)
    {
        return $this->refundItemModel->insert($attributes);
    }
}
