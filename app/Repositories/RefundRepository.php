<?php

namespace App\Repositories;

use App\Models\Refund;

class RefundRepository
{
    /**
     * @var Refund
     */
    protected $refundModel;

    /**
     * @param Refund $refundModel
     */
    public function __construct(Refund $refundModel)
    {
        $this->refundModel = $refundModel;
    }

    public function setConnection($connection)
    {
        $this->refundModel->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->refundModel->getConnectionName();
    }

    public function create($attributes)
    {
        return $this->refundModel->create($attributes);
    }

    /**
     * @param array  $where
     * @param string $field
     *
     * @return object
     */
    public function fetch(array $where, string $field = '*'): object
    {
        return $this->refundModel->select($field)->where($where)->get();
    }
}
