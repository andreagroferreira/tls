<?php

namespace App\Repositories;

use App\Models\RefundItem;
use App\Models\Transactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransactionRepository
{
    /**
     * @var Transactions
     */
    protected $transactionModel;

    /**
     * @var RefundItem
     */
    protected $refundItemModel;

    /**
     * @var int
     */
    private $pageLimit = 50000;

    public function __construct(Transactions $transactionModel, RefundItem $refundItemModel)
    {
        $this->transactionModel = $transactionModel;
        $this->refundItemModel = $refundItemModel;
    }

    public function setConnection($connection)
    {
        $this->transactionModel->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->transactionModel->getConnectionName();
    }

    public function fetch($where, $field = '*')
    {
        return $this->transactionModel
            ->select($field)
            ->where($where)
            ->get();
    }

    /**
     * Gets the done transactions based on an array of transaction ids
     *
     * @param array $transactionsIds
     * @return Collection<Transactions>
     */
    public function fetchDoneTransactionsByTransactionIds(array $transactionsIds)
    {
        return $this->transactionModel
            ->whereIn('t_transaction_id', $transactionsIds)
            ->where('t_status', 'done')
            ->orderBy('t_transaction_id')
            ->get();
    }

    public function fetchWithPage($where, $limit, $issuer = null)
    {
        return $this->transactionModel
            ->where($where)
            ->when($issuer, function ($query) use ($issuer) {
                return $query->whereIn('t_issuer', $issuer);
            })
            ->orderBy('t_tech_creation', 'desc')
            ->paginate($limit);
    }

    public function fetchByFgId($attributes)
    {
        return $this->transactionModel
            ->select([
                't_id',
                't_gateway AS gateway',
                't_payment_method AS agent_gateway',
                't_transaction_id AS transaction_id',
                't_gateway_transaction_id AS gateway_transaction_id',
                't_currency AS currency',
                't_status AS status',
                't_service AS service',
                't_agent_name as agent',
                't_invoice_storage AS invoice_storage',
                't_tech_creation AS tech_creation',
                't_tech_modification AS tech_modification',
            ])
            ->where([
                ['t_xref_fg_id', '=', $attributes['fg_id']],
                ['t_tech_deleted', '=', false],
            ])
            ->where(function ($query) {
                //get all transactions where t_status is done
                $query->where('t_status', 'done')
                    ->OrWhere(function ($query) {
                        //get all transactions where t_status not equal to close and transaction not expired
                        $query->where('t_status', '<>', 'close')
                            ->where(function ($subQuery) {
                                $subQuery->whereNull('t_expiration')
                                    ->orWhere('t_expiration', '>', 'now()');
                            })
                            ->where(function ($subQuery) {
                                $subQuery->whereNull('t_gateway_expiration')
                                    ->Orwhere('t_gateway_expiration', '>', 'now()');
                            });
                    });
            })
            ->orderBY('t_id', $attributes['order'])
            ->get();
    }

    public function create($attributes)
    {
        return $this->transactionModel->create($attributes);
    }

    public function update($where, $attributes)
    {
        $transaction = $this->transactionModel->where($where)->first();

        if (blank($transaction)) {
            return false;
        }

        foreach ($attributes as $key => $value) {
            $transaction->{$key} = $value;
        }
        $transaction->save();

        return $this->transactionModel->find($transaction->t_id);
    }

    /**
     * @return mixed
     */
    public function getTransactionIdSeq()
    {
        $res = DB::connection($this->getConnection())->select("SELECT nextval('transactions_t_id_seq')");

        return array_first($res)->nextval;
    }

    /**
     * @param array $attributes
     *
     * @return string
     */
    public function findBy(array $attributes): string
    {
        $result = $this->transactionModel;
        foreach ($attributes as $key => $value) {
            $result = $result->where($key, '=', $value);
        }

        return $result->get();
    }

    /**
     * @param Collection $where
     * @param Collection $dateConditionTransaction
     * @param Collection $dateConditionRefund
     * @param int        $limit
     * @param string     $orderField
     * @param string     $order
     *
     * @return array
     */
    public function listTransactions(
        Collection $where,
        Collection $dateConditionTransaction,
        Collection $dateConditionRefund,
        int $limit,
        string $orderField,
        string $order
    ): array {
        $condition = $where->push(
            ['t_tech_deleted', '=', false],
            ['t_status', '=', 'done'],
        )->toArray();

        if ($orderField == 'country' || $orderField == 'city') {
            $orderField = 't_id';
        }

        $refundQuery = $this->refundItemModel
            ->leftJoin('transaction_items', 'transaction_items.ti_id', '=', 'refund_items.ri_xref_ti_id')
            ->leftJoin('transactions', 'transactions.t_transaction_id', '=', 'transaction_items.ti_xref_transaction_id')
            ->leftJoin('payment_accounts', 'payment_accounts.pa_id', '=', 'transactions.t_xref_pa_id')
            ->leftJoin('refund_logs', function ($join) {
                $join->on('refund_logs.rl_xref_ri_id', '=', 'refund_items.ri_id');
                $join->on('refund_logs.rl_xref_r_id', '=', 'refund_items.ri_xref_r_id');
                $join->where('refund_logs.rl_type', '=', 'status_change');
            })
            ->where($dateConditionRefund->toArray())
            ->where($condition)
            ->where('refund_items.ri_status', 'done')
            ->select([
                't_xref_fg_id',
                'ti_xref_f_id',
                't_id',
                't_transaction_id',
                't_client',
                't_issuer',
                't_service',
                DB::raw('(CASE WHEN t_xref_pa_id IS NULL THEN t_payment_method ELSE pa_name END) AS t_payment_method'),
                't_gateway',
                't_gateway_transaction_id',
                't_currency',
                't_invoice_storage',
                't_workflow',
                't_status',
                't_appointment_date',
                't_appointment_time',
                'ri_tech_modification AS modification_date',
                'ti_id',
                'ti_fee_type',
                'ri_amount AS amount',
                'ti_vat',
                'ti_price_rule',
                'ti_fee_name',
                'ti_label',
                'ti_tag',
                'rl_agent AS agent',
            ])
            ->selectRaw('ri_id')
            ->selectRaw('ri_quantity*-1 AS quantity')
            ->selectRaw('ri_amount-(ti_vat/100 * ri_amount) AS amount_without_tax')
            ->selectRaw('SUBSTR(t_issuer, 1, 2) AS country_code')
            ->selectRaw('SUBSTR(t_issuer, 3, 3) AS city_code');

        return $this->transactionModel
            ->join('transaction_items', 'transactions.t_transaction_id', '=', 'ti_xref_transaction_id')
            ->leftJoin('payment_accounts', 'payment_accounts.pa_id', '=', 'transactions.t_xref_pa_id')
            ->where($dateConditionTransaction->toArray())
            ->where($condition)
            ->select([
                't_xref_fg_id',
                'ti_xref_f_id',
                't_id',
                't_transaction_id',
                't_client',
                't_issuer',
                't_service',
                DB::raw('(CASE WHEN t_xref_pa_id IS NULL THEN t_payment_method ELSE pa_name END) AS t_payment_method'),
                't_gateway',
                't_gateway_transaction_id',
                't_currency',
                't_invoice_storage',
                't_workflow',
                't_status',
                't_appointment_date',
                't_appointment_time',
                't_tech_modification AS modification_date',
                'ti_id',
                'ti_fee_type',
                'ti_amount AS amount',
                'ti_vat',
                'ti_price_rule',
                'ti_fee_name',
                'ti_label',
                'ti_tag',
                't_agent_name as agent'
            ])
            ->selectRaw('NULL AS ri_id')
            ->selectRaw('ti_quantity AS quantity')
            ->selectRaw('ti_amount-(ti_vat/100 * ti_amount) AS amount_without_tax')
            ->selectRaw('SUBSTR(t_issuer, 1, 2) AS country_code')
            ->selectRaw('SUBSTR(t_issuer, 3, 3) AS city_code')
            ->union($refundQuery)
            ->orderBY($orderField, $order)
            ->paginate($limit)
            ->toArray();
    }

    /**
     * @param Collection $where
     * @param Collection $dateConditionTransaction
     * @param Collection $dateConditionRefund
     *
     * @return array
     */
    public function listTransactionsSkuSummary(
        Collection $where,
        Collection $dateConditionTransaction,
        Collection $dateConditionRefund
    ): array {
        $condition = $where->push(
            ['t_tech_deleted', '=', false],
            ['t_status', '=', 'done'],
        )->toArray();

        $applicantQuery = $this->transactionModel
            ->leftJoin('transaction_items', 'transactions.t_transaction_id', '=', 'ti_xref_transaction_id')
            ->leftJoin('refund_items', 'refund_items.ri_xref_ti_id', '=', 'transaction_items.ti_id')
            ->where($condition)
            ->whereNotNull('transactions.t_xref_pa_id')
            ->whereNull('transactions.t_payment_method')
            ->where(function($query) use($dateConditionTransaction, $dateConditionRefund) {
                $query->where($dateConditionTransaction->toArray())
                    ->orWhere($dateConditionRefund->toArray());
            })
            ->select([
                'ti_fee_type AS sku',
                DB::raw('\'online\' as payment_method'),
                't_currency AS currency'
            ])
            ->selectRaw('CAST(SUM(ti_amount) AS DECIMAL) + 
                        CAST(
                            SUM(
                                CASE
                                    WHEN ri_id IS NOT NULL THEN (ri_amount*-1)
                                    ELSE 0
                                END
                            ) AS DECIMAL
                        ) AS amount')
            ->groupBY('ti_fee_type', 't_payment_method', 't_currency');

        $agentQuery = $this->transactionModel
            ->leftJoin('transaction_items', 'transactions.t_transaction_id', '=', 'ti_xref_transaction_id')
            ->leftJoin('refund_items', 'refund_items.ri_xref_ti_id', '=', 'transaction_items.ti_id')
            ->where($condition)
            ->whereNotNull('transactions.t_payment_method')
            ->where(function($query) use($dateConditionTransaction, $dateConditionRefund) {
                $query->where($dateConditionTransaction->toArray())
                    ->orWhere($dateConditionRefund->toArray());
            })
            ->select([
                'ti_fee_type AS sku',
                't_payment_method AS payment_method',
                't_currency AS currency'
            ])
            ->selectRaw('CAST(SUM(ti_amount) AS DECIMAL) + 
                        CAST(
                            SUM(
                                CASE
                                    WHEN ri_id IS NOT NULL THEN (ri_amount*-1)
                                    ELSE 0
                                END
                            ) AS DECIMAL
                        ) AS amount')
            ->groupBY('ti_fee_type', 't_payment_method', 't_currency');

        return $applicantQuery
            ->unionAll($agentQuery)
            ->get()
            ->toArray();
    }

    /**
     * @param Collection $where
     * @param Collection $dateConditionTransaction
     * @param Collection $dateConditionRefund
     * @param string     $orderField
     * @param string     $order
     *
     * @return array
     */
    public function exportTransactionsToCsv(
        Collection $where,
        Collection $dateConditionTransaction,
        Collection $dateConditionRefund,
        string $orderField,
        string $order
    ): array {
        return $this->listTransactions(
            $where,
            $dateConditionTransaction,
            $dateConditionRefund,
            $this->pageLimit,
            $orderField,
            $order
        );
    }

    /**
     * @param int   $tId
     * @param array $data
     *
     * @return int
     */
    public function updateById(int $tId, array $data)
    {
        return $this->transactionModel->where('t_id', $tId)->update($data);
    }
}
