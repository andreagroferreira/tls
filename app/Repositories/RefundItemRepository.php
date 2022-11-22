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
                'r_id',
                'r_issuer',
                'r_reason_type',
                'r_status',
                'r_appointment_date',
            ])
            ->get();
    }


}
