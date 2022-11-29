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
    
    /**
     * @param  array  $where
     * @param  string $field
     * 
     * @return object
     */
    public function fetch(array $where, string $field = '*'): object
    {
        return $this->refundItemModel->select($field)->where($where)->get();
    }
    
    /**
     * @param  array $where
     * 
     * @return object
     */
    public function fetchRefundItems(array $where): object
    {
        return $this->refundItemModel
                    ->leftjoin('transaction_items', 'refund_items.ri_xref_ti_id', '=', 'transaction_items.ti_id')
                    ->leftjoin('transactions', 'transactions.t_transaction_id', '=', 'ti_xref_transaction_id')
                    ->leftJoin('refunds', function ($join) {
                        $join->on('refund_items.ri_xref_r_id', '=', 'r_id');
                    })
                    ->leftJoin('refund_logs', function ($join) {
                        $join->on('refund_logs.rl_xref_ri_id', '=', 'refund_items.ri_id');
                        $join->on('refund_logs.rl_xref_r_id', '=', 'refund_items.ri_xref_r_id');
                        $join->where('refund_logs.rl_type', '=', 'status_change');
                    })
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
                        't_id',
                        't_xref_fg_id',
                        'ti_xref_f_id',
                        't_transaction_id',
                        't_client',
                        't_issuer',
                        't_service',
                        't_payment_method',
                        't_gateway',
                        't_gateway_transaction_id',
                        't_payment_method',
                        't_currency',
                        't_invoice_storage',
                        't_issuer',
                        't_workflow',
                        't_status',
                        't_tech_creation',
                        't_tech_modification',
                        'ti_id',
                        'rl_agent',
                    ])
                    ->selectRaw('SUBSTR(t_issuer, 1, 2) AS country_code')
                    ->selectRaw('SUBSTR(t_issuer, 3, 3) AS city_code')
                    ->get();
    }
}
