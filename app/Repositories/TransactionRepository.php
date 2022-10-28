<?php


namespace App\Repositories;

use App\Models\Transactions;
use Illuminate\Support\Facades\DB;

class TransactionRepository
{
    protected $transactionModel;

    public function __construct(Transactions $transactionModel)
    {
        $this->transactionModel = $transactionModel;
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
                't_invoice_storage AS invoice_storage',
                't_tech_creation AS tech_creation',
                't_tech_modification AS tech_modification',
            ])
            ->where([
                ['t_xref_fg_id', '=', $attributes['fg_id']],
                ['t_tech_deleted', '=', false]
            ])
            ->where(function ($query) {
                //get all transactions where t_status is done
                $query->where('t_status', 'done')
                    ->OrWhere(function ($query) {
                        //get all transactions where t_status not equal to close and transaction not expired
                        $query->where('t_status', '<>', 'close')
                            ->where(function($sub_query) {
                                $sub_query->whereNull('t_expiration')
                                    ->orWhere('t_expiration', '>', 'now()');
                            })
                            ->where(function($sub_query) {
                                $sub_query->whereNull('t_gateway_expiration')
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
            $transaction->$key = $value;
        }
        $transaction->save();

        return $this->transactionModel->find($transaction->t_id);
    }

    public function getTransactionIdSeq()
    {
        $res = DB::connection($this->getConnection())->select("SELECT nextval('transactions_t_id_seq')");

        return array_first($res)->nextval;
    }

    public function findBy($attributes) {
        $result = $this->transactionModel;
        foreach ($attributes as $key => $value) {
            $result = $result->where($key, '=', $value);
        }
        return $result->get();
    }

    /**
     * @param array  $where
     * @param int    $limit
     * @param string $order_field
     * @param string $order
     *
     * @return object
     */
    public function fetchTransactionsWithPage(array $where, int $limit, string $order_field, string $order): object
    {
        return $this->transactionModel
            ->join('transaction_items', 'transactions.t_transaction_id', '=', 'transaction_items.ti_xref_transaction_id')
            ->where($where)
            ->select([
                't_id',
                't_tech_creation',
                't_client',
                't_xref_fg_id',
                't_transaction_id',
                't_service',
                'ti_fee_type',
                'ti_quantity',
                'ti_amount',
                'ti_vat',
                't_payment_method',
                't_gateway',
                't_gateway_transaction_id',
                't_payment_method',
                't_currency',
                't_invoice_storage',
                't_issuer',
            ])
            ->selectRaw('(ti_vat/100 * ti_amount)+ti_amount AS amount_gross')
            ->selectRaw('SUBSTR(t_issuer, 1, 2) AS country_code')
            ->selectRaw('SUBSTR(t_issuer, 3, 3) AS city_code')
            ->orderBY($order_field, $order)
            ->paginate($limit);
    }
}
