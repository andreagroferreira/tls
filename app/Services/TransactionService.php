<?php

namespace App\Services;

use App\Repositories\TransactionRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    protected $transactionRepository;
    protected $dbConnectionService;
    protected $transactionItemsService;

    public function __construct(
        TransactionRepository $transactionRepository,
        DbConnectionService $dbConnectionService,
        TransactionItemsService $transactionItemsService
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->dbConnectionService = $dbConnectionService;
        $this->transactionItemsService = $transactionItemsService;
        $this->transactionRepository->setConnection($this->dbConnectionService->getConnection());
    }

    public function updateById($t_id, $attributes)
    {
        return $this->transactionRepository->update(['t_id' => $t_id], $attributes);
    }

    public function fetch($attributes)
    {
        $transactions = $this->transactionRepository->fetchByFgID($attributes);

        if ($transactions->isEmpty()) {
            return false;
        }

        return $transactions->transform(function ($item) {
            $item->items = $this->transactionItemsService->fetchItemsByTransactionId($item->transaction_id);

            return $item;
        });
    }

    public function fetchByForm($params)
    {
        $transactions = $this->transactionItemsService->fetch([
            'ti_xref_f_id' => $params['f_id'],
        ])
            ->whereNotIn('ti_fee_type', ['service_fees', 'visa_fees'])
            ->groupBy('ti_xref_transaction_id')
            ->toArray();

        if (empty($transactions)) {
            return [];
        }

        $receipts = [];
        foreach ($transactions as $transaction_id => $services) {
            $transaction = $this->transactionRepository->fetch([
                't_transaction_id' => $transaction_id,
                't_status' => 'done',
            ])->first();
            if (!empty($transaction)) {
                $items['f_id'] = current($services)['ti_xref_f_id'];
                $items['skus'] = [];
                foreach ($services as $service) {
                    $items['skus'][] = [
                        'sku' => $service['ti_fee_type'],
                        'price' => $service['ti_amount'],
                        'vat' => $service['ti_vat'],
                        'quantity' => $service['ti_quantity'],
                    ];
                }
                $receipts[] = [
                    't_id' => $transaction->t_id,
                    'gateway' => $transaction->t_gateway,
                    'agent_gateway' => $transaction->t_payment_method,
                    'transaction_id' => $transaction_id,
                    'gateway_transaction_id' => $transaction->t_gateway_transaction_id,
                    'currency' => $transaction->t_currency,
                    'status' => 'done',
                    'service' => $transaction->t_service,
                    'tech_creation' => $transaction->t_tech_creation,
                    'tech_modification' => $transaction->t_tech_modification,
                    'items' => [$items],
                ];
            }
        }
        $sort = SORT_DESC;
        if (!empty($params['sort']) && $params['sort'] == 'asc') {
            $sort = SORT_ASC;
        }

        return collect($receipts)->sortBy('t_id', $sort)->values()->toArray();
    }

    public function fetchAll($attributes)
    {
        $where = collect([
            ['t_tech_deleted', '=', false],
            ['t_tech_creation', '>=', $attributes['start_date']],
            ['t_tech_creation', '<', $attributes['end_date']],
        ])
            ->when(array_key_exists('status', $attributes), function ($collect) use ($attributes) {
                return $collect->push(['t_status', '=', $attributes['status']]);
            })
            ->when(array_key_exists('service', $attributes), function ($collect) use ($attributes) {
                return $collect->push(['t_service', '=', $attributes['service']]);
            })
            ->toArray();

        $res = $this->transactionRepository->fetchWithPage(
            $where,
            $attributes['limit'],
            array_get($attributes, 'issuer')
        )
            ->toArray();

        return [
            'total' => array_get($res, 'total', 0),
            'data' => array_get($res, 'data', []),
        ];
    }

    public function fetchByWhere($where, $field = '*')
    {
        return $this->transactionRepository->fetch($where, $field);
    }

    public function checkDuplicateCreation($attributes)
    {
        $transaction = $this->transactionRepository
            ->fetch(['t_xref_fg_id' => $attributes['fg_id'], 't_status' => 'pending', 't_tech_deleted' => false])
            ->first();

        if (blank($transaction)) {
            return true;
        }

        $now = Carbon::parse($this->dbConnectionService->getDbNowTime());
        if (
            (!is_null($transaction->t_expiration) && $now->gt($transaction->t_expiration))
            || (!is_null($transaction->t_gateway_expiration) && $now->gt($transaction->t_gateway_expiration))
        ) {
            $this->transactionRepository->update(
                ['t_id' => $transaction->t_id],
                ['t_status' => 'close', 't_tech_modification' => $now]
            );

            return true;
        }

        $is_change = false;
        $res = $this->convertItemsFieldToArray(
            $transaction->t_transaction_id,
            $attributes['items'],
            ['ti_tech_deleted' => false]
        );
        $transItems = $this->transactionItemsService->fetch(
            ['ti_xref_transaction_id' => $transaction->t_transaction_id, 'ti_tech_deleted' => false],
            ['ti_xref_f_id', 'ti_xref_transaction_id', 'ti_fee_type', 'ti_vat', 'ti_amount', 'ti_tech_deleted']
        )->toArray();
        if (count($res) !== count($transItems)) {
            $is_change = true;
        } else {
            foreach ($res as $key => $item) {
                if ($this->transactionItemsService->fetch($item)->isEmpty()) {
                    $is_change = true;

                    break;
                }
                unset($res[$key]);
            }
        }
        if ($is_change || filled($res)) {
            $this->transactionRepository->update(
                ['t_id' => $transaction->t_id],
                ['t_status' => 'close', 't_tech_modification' => $now]
            );

            return true;
        }

        return ['t_id' => $transaction->t_id, 'expire' => $transaction->t_expiration];
    }

    public function create(array $attributes)
    {
        $transaction_data = [
            't_id' => $this->transactionRepository->getTransactionIdSeq(),
            't_xref_fg_id' => $attributes['fg_id'],
            't_client' => $attributes['client'],
            't_issuer' => $attributes['issuer'],
            't_redirect_url' => $attributes['redirect_url'],
            't_onerror_url' => $attributes['onerror_url'],
            't_reminder_url' => $attributes['reminder_url'],
            't_callback_url' => $attributes['callback_url'],
            't_currency' => $attributes['currency'],
            't_workflow' => $attributes['workflow'],
            't_invoice_storage' => $attributes['invoice_storage'] ?? 'file-library',
        ];

        $expirationMinutes = config('payment_gateway.expiration_minutes') ?? 60;

        if (!empty($attributes['t_expiration'])) {
            $expirationMinutes = $attributes['t_expiration'];
        }

        $transaction_data['t_expiration'] = Carbon::parse($this->dbConnectionService->getDbNowTime())
            ->addMinutes($expirationMinutes);

        if (isset($attributes['payment_method'])) {
            $transaction_data['t_payment_method'] = $attributes['payment_method'];
        }

        if (isset($attributes['service']) && $attributes['service'] == 'gov') {
            $transaction_data['t_service'] = $attributes['service'];
        }
        $transaction_data['t_transaction_id'] = $this->generateTransactionId(
            $transaction_data['t_id'],
            $transaction_data['t_issuer']
        );

        $db_connection = DB::connection($this->dbConnectionService->getConnection());
        $db_connection->beginTransaction();

        try {
            $transaction = $this->transactionRepository->create($transaction_data);
            $this->transactionItemsService->createMany(
                $this->convertItemsFieldToArray(
                    $transaction->t_transaction_id,
                    $attributes['items']
                )
            );

            $db_connection->commit();
        } catch (\Exception $e) {
            $db_connection->rollBack();

            return false;
        }

        return ['t_id' => $transaction->t_id,
            'expire' => Carbon::parse($transaction->t_expiration)->toDateTimeString(), ];
    }

    public function update($transaction_id, $attributes)
    {
        return $this->transactionRepository->update($transaction_id, $attributes);
    }

    public function getTransaction($t_id): array
    {
        $transaction = $this->transactionRepository->fetch(['t_id' => $t_id])->first();
        if (empty($transaction)) {
            return [];
        }
        $transaction = $transaction->toArray();
        $transaction_id = $transaction['t_transaction_id'];
        $transaction_items = $this->transactionItemsService->fetchItemsByTransactionId($transaction_id);
        $transaction_items = filled($transaction_items) ? $transaction_items->toArray() : [];
        $amount = 0;
        foreach ($transaction_items as $transaction_item) {
            foreach ($transaction_item['skus'] as $sku) {
                $amount += $sku['price'];
            }
        }
        $transaction['t_amount'] = $amount;
        $transaction['t_items'] = $transaction_items;

        return $transaction;
    }

    public function fetchTransaction($attributes): array
    {
        $transaction = $this->transactionRepository->fetch($attributes);
        if ($transaction->isEmpty()) {
            return [];
        }
        $t_id = $transaction->first()->t_id;

        return $this->getTransaction($t_id);
    }

    public function fetchByTransactionId($transaction_id)
    {
        return $this->transactionRepository->findBy([
            't_id' => $transaction_id,
            't_tech_deleted' => false,
        ])->first();
    }

    public function getDbNowTime()
    {
        return Carbon::parse($this->dbConnectionService->getDbNowTime());
    }

    public function getDbTimeZone()
    {
        return Carbon::parse($this->dbConnectionService->getDbNowTime())->getTimezone()->toRegionName();
    }

    /**
     * @param array $attributes
     *
     * @return array
     */
    public function listTransactions(array $attributes): array
    {
        $fullTextSearchColumn = ['ti_fee_type', 't_comment', 't_reference_id'];

        $allowedColumns = [
            't_country',
            't_city',
            'ti_fee_type',
            't_reference_id',
            't_comment',
            't_xref_fg_id',
            't_client',
            't_batch_id',
            'ti_quantity',
        ];

        $where = collect();

        if (!empty($attributes['start_date']) && !empty($attributes['end_date'])) {
            $where->push(
                ['t_tech_creation', '>=', $attributes['start_date'].' 00:00:00'],
                ['t_tech_creation', '<=', $attributes['end_date'].' 23:59:59']
            );
        }

        if (!empty($attributes['multi_search'])) {
            $issuer = array_get($attributes['multi_search'], 't_country').
                      array_get($attributes['multi_search'], 't_city');

            unset($attributes['multi_search']['t_country'], $attributes['multi_search']['t_city']);

            $data = array_filter($attributes['multi_search']);
            foreach ($data as $column => $value) {
                if (!in_array($column, $allowedColumns)) {
                    continue;
                }

                if (in_array($column, $fullTextSearchColumn)) {
                    $where->push([$column, 'LIKE', '%'.$value.'%']);
                } else {
                    $where->push([$column, '=', $value]);
                }
            }

            if (!empty($issuer)) {
                $where->push(['t_issuer', 'LIKE', '%'.$issuer.'%']);
            }
        }

        if ($attributes['csv']) {
            $transactions = $this->transactionRepository->exportTransactionsToCsv(
                $where,
                $attributes['order_field'],
                $attributes['order']
            );
        } else {
            $transactions = $this->transactionRepository->listTransactions(
                $where,
                $attributes['limit'],
                $attributes['order_field'],
                $attributes['order']
            );
        }

        if (empty($transactions)) {
            return [];
        }

        foreach ($transactions['data'] as $k => $details) {
            $transactions['data'][$k]['country'] = getCountryName($details['country_code']);
            $transactions['data'][$k]['city'] = getCityName($details['city_code']);
            $transactions['data'][$k]['receipt_url'] = getFilePath($details, $details['t_invoice_storage']);
        }

        return [
            'total' => array_get($transactions, 'total', 0),
            'data' => array_get($transactions, 'data', []),
            'current_page' => array_get($transactions, 'current_page', 1),
        ];
    }

    /**
     * @param array $result
     *
     * @return array
     */
    public function writeTransactionsToCsv(array $result): array
    {
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=download.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];
        $columns = [
            'Client',
            'Country',
            'City',
            'Date of transaction',
            'Transaction ID',
            'Group ID',
            'Basket type',
            'SKU',
            'Payment type',
            'Gateway transaction ID',
            'Currency',
            'Amount NET',
            'VAT',
            'Amount Gross',
            'Quantity',
            'Agent',
        ];
        $fields = [
            't_client',
            'country',
            'city',
            't_tech_creation',
            't_transaction_id',
            't_xref_fg_id',
            't_service',
            'ti_fee_type',
            't_payment_method',
            't_gateway_transaction_id',
            't_currency',
            'amount',
            'ti_vat',
            'amount_gross',
            'quantity',
            'agent',
        ];

        $callback = function () use ($result, $columns, $fields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($result as $details) {
                $row = [];
                foreach ($fields as $v) {
                    $row[$v] = $details[$v];
                }
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return [
            'callback' => $callback,
            'headers' => $headers,
        ];
    }

    protected function generateTransactionId($transaction_id_seq, $issuer)
    {
        $environment = env('APPLICATION_ENV') == 'prod' ? '' : strtoupper(env('APPLICATION_ENV')).date('Ymd').'-';
        $project = env('PROJECT') ? env('PROJECT').'-' : '';

        return $project.$environment.$issuer.'-'.str_pad($transaction_id_seq, 10, '0', STR_PAD_LEFT);
    }

    protected function convertItemsFieldToArray($transaction_id, $items_field, $add_field = [])
    {
        $response = [];
        foreach (json_decode($items_field, true) as $items) {
            foreach ($items['skus'] as $sku) {
                $res = [
                    'ti_xref_f_id' => $items['f_id'],
                    'ti_xref_transaction_id' => $transaction_id,
                    'ti_fee_type' => $sku['sku'],
                    'ti_vat' => $sku['vat'],
                    'ti_amount' => $sku['price'],
                ];
                //agent receipt is used
                if (isset($sku['quantity'])) {
                    $res['ti_quantity'] = $sku['quantity'];
                }
                if (filled($add_field)) {
                    $res = $res + $add_field;
                }

                $response[] = $res;
            }
        }

        return $response;
    }
}
