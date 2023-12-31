<?php

namespace App\Repositories;

use App\Models\RefundItem;

class RefundItemRepository
{
    /**
     * @var RefundItem
     */
    protected $refundItemModel;

    /**
     * @param RefundItem $refundItemModel
     */
    public function __construct(RefundItem $refundItemModel)
    {
        $this->refundItemModel = $refundItemModel;
    }

    /**
     * @param $connection
     *
     * @return void
     */
    public function setConnection($connection)
    {
        $this->refundItemModel->setConnection($connection);
    }

    /**
     * @return null|string
     */
    public function getConnection()
    {
        return $this->refundItemModel->getConnectionName();
    }

    /**
     * @param array $attributes
     *
     * @return bool
     */
    public function createMany(array $attributes): bool
    {
        return $this->refundItemModel->insert($attributes);
    }

    /**
     * @param array  $where
     * @param string $field
     *
     * @return object
     */
    public function fetch(array $where, string $field = '*'): object
    {
        return $this->refundItemModel->select($field)->where($where)->get();
    }

    /**
     * @param array $where
     *
     * @return object
     */
    public function fetchRefundItems(array $where): object
    {
        return $this->refundItemModel
            ->join('transaction_items', 'refund_items.ri_xref_ti_id', '=', 'transaction_items.ti_id')
            ->join('refunds', 'refund_items.ri_xref_r_id', '=', 'refunds.r_id')
            ->where($where)
            ->select([
                'ri_id',
                'ri_xref_r_id',
                'ri_xref_ti_id',
                'ri_quantity',
                'ri_amount',
                'ri_reason_type',
                'ri_status',
                'ri_invoice_path',
                'ti_xref_transaction_id',
                'ti_price_rule',
                'ti_fee_name',
                'r_id',
                'r_issuer',
                'r_reason_type',
                'r_status',
            ])
            ->get();
    }
}
