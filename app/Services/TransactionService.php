<?php

namespace App\Services;

use App\Jobs\InvoiceMailJob;
use App\Jobs\TransactionSyncJob;
use App\Jobs\TransactionSyncToEcommerceJob;
use App\Jobs\TransactionSyncToWorkflowJob;
use App\Repositories\TransactionRepository;
use App\Traits\FeatureVersionsTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    use FeatureVersionsTrait;

    protected $transactionRepository;
    protected $dbConnectionService;
    protected $transactionItemsService;
    protected $formGroupService;
    protected $transactionLogsService;

    public function __construct(
        TransactionRepository $transactionRepository,
        DbConnectionService $dbConnectionService,
        TransactionItemsService $transactionItemsService,
        FormGroupService $formGroupService,
        TransactionLogsService $transactionLogsService
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->dbConnectionService = $dbConnectionService;
        $this->transactionItemsService = $transactionItemsService;
        $this->formGroupService = $formGroupService;
        $this->transactionLogsService = $transactionLogsService;
        $this->transactionRepository->setConnection($this->dbConnectionService->getConnection());
    }

    public function updateById($t_id, $attributes)
    {
        return $this->transactionRepository->update(['t_id' => $t_id], $attributes);
    }

    public function fetch($attributes)
    {
        $transactions = $this->transactionRepository->fetchByFgId($attributes);

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
                        'price_rule' => $service['ti_price_rule'],
                        'product_name' => $service['ti_fee_name'],
                        'label' => $service['ti_label'],
                        'tag' => $service['ti_tag'],
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

    /**
     * @param array $attributes
     *
     * @return null|array
     */
    public function getOrCloseDuplicatedTransaction($attributes): ?array
    {
        if (empty($attributes['service'])) {
            $attributes['service'] = 'tls';
        }

        $duplicatedTransaction = $this->transactionRepository
            ->fetch([
                't_xref_fg_id' => $attributes['fg_id'],
                't_status' => 'pending',
                't_service' => $attributes['service'],
                't_tech_deleted' => false,
            ])->first();
        if (blank($duplicatedTransaction)) {
            return null;
        }

        $now = Carbon::parse($this->dbConnectionService->getDbNowTime());
        if (!empty($attributes['agent_name']) && !empty($attributes['payment_method'])) {
            if ($this->closeTransaction($duplicatedTransaction, $now)) {
                return null;
            }
        }

        if ($this->canTransactionBeClosed($duplicatedTransaction, $now)) {
            if ($this->closeTransaction($duplicatedTransaction, $now)) {
                return null;
            }
        }

        $res = $this->convertItemsFieldToArray(
            $duplicatedTransaction->t_transaction_id,
            $attributes['items'],
            ['ti_tech_deleted' => false]
        );

        $transItems = $this->transactionItemsService->fetch(
            ['ti_xref_transaction_id' => $duplicatedTransaction->t_transaction_id, 'ti_tech_deleted' => false],
            ['ti_xref_f_id', 'ti_xref_transaction_id', 'ti_fee_type', 'ti_vat', 'ti_amount', 'ti_tech_deleted', 'ti_price_rule']
        )->toArray();

        if ($this->areItemsChanged($res, $transItems) || filled($res)) {
            if ($this->closeTransaction($duplicatedTransaction, $now)) {
                return null;
            }
        }

        return ['t_id' => $duplicatedTransaction->t_id, 'expire' => $duplicatedTransaction->t_expiration];
    }

    public function create(array $attributes)
    {
        $invoiceStorage = 'file-library';
        if ($this->isVersion(1, $attributes['issuer'], 'invoice')) {
            $invoiceStorage = env('INVOICE_STORAGE', 's3');
        }
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
            't_invoice_storage' => $invoiceStorage,
        ];

        if (!empty($attributes['expiration'])) {
            $transaction_data['t_expiration'] = Carbon::createFromTimestamp($attributes['expiration']);
        } else {
            $transaction_data['t_expiration'] = Carbon::parse($this->dbConnectionService->getDbNowTime())
                ->addMinutes(config('payment_gateway.expiration_minutes') ?? 60);
        }

        if ($transaction_data['t_expiration']->isPast()) {
            throw new \Exception('The expiration time is less then current time.');
        }

        if (!empty($attributes['payment_method'])) {
            $transaction_data['t_payment_method'] = $attributes['payment_method'];
        }

        if (isset($attributes['service']) && $attributes['service'] == 'gov') {
            $transaction_data['t_service'] = $attributes['service'];
        }

        if (!empty($attributes['agent_name'])) {
            $transaction_data['t_agent_name'] = $attributes['agent_name'];
        }

        if (!empty($attributes['appointment_date']) && !empty($attributes['appointment_time'])) {
            $transaction_data['t_appointment_date'] = $attributes['appointment_date'];
            $transaction_data['t_appointment_time'] = $attributes['appointment_time'];
        }

        $transaction_data['t_transaction_id'] = $this->generateTransactionId(
            $transaction_data['t_id'],
            $transaction_data['t_issuer']
        );

        $db_connection = DB::connection($this->dbConnectionService->getConnection());
        $db_connection->beginTransaction();

        try {
            $transaction = $this->transactionRepository->create($transaction_data);
            $transactionItems = $this->convertItemsFieldToArray($transaction->t_transaction_id, $attributes['items']);

            $totalAmount = 0.00;
            foreach ($transactionItems as $item) {
                $totalAmount += (float) $item['ti_amount'] * Arr::get($item, 'ti_quantity', 1);
            }

            $this->transactionItemsService->createMany(
                $transactionItems
            );

            $transactionData = $this->getTransaction($transaction->t_id);
            if ($totalAmount === 0.00 && $this->isVersion(1, $transaction['t_issuer'], 'transaction_sync')) {
                PaymentService::confirmTransaction($transactionData, [
                    'gateway' => 'free',
                    'amount' => $transactionData['t_amount'],
                    'currency' => $transactionData['t_currency'],
                    'transaction_id' => $transactionData['t_transaction_id'],
                    'gateway_transaction_id' => $transactionData['t_transaction_id'],
                ]);
            } else if ($totalAmount === 0.00 || (!empty($attributes['agent_name']) && !empty($attributes['payment_method']))) {
                $this->confirmTransaction($transactionData);
            }

            $db_connection->commit();
        } catch (\Exception $e) {
            $db_connection->rollBack();

            return false;
        }

        return [
            't_id' => $transaction->t_id,
            'expire' => Carbon::parse($transaction->t_expiration)->toDateTimeString(),
        ];
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
                $amount += $sku['price'] * Arr::get($sku, 'quantity', 1);
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
            'ti_xref_f_id',
            't_service',
            'ti_fee_name',
            't_payment_method',
            't_transaction_id',
            't_gateway_transaction_id',
            't_gateway',
            't_currency',
            'ti_price_rule',
            'ti_vat',
            't_agent_name',
        ];

        $where = collect();
        $dateConditionTransaction = collect();
        $dateConditionRefund = collect();

        if (!empty($attributes['start_date']) && !empty($attributes['end_date'])) {
            $dateConditionTransaction->push(
                ['t_tech_modification', '>=', $attributes['start_date']],
                ['t_tech_modification', '<=', $attributes['end_date']]
            );
            $dateConditionRefund->push(
                ['ri_tech_modification', '>=', $attributes['start_date']],
                ['ri_tech_modification', '<=', $attributes['end_date']]
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
                    $where->push([$column, 'ILIKE', '%'.$value.'%']);
                } else {
                    $where->push([$column, 'ILIKE', $value]);
                }
            }

            if (!empty($issuer)) {
                $where->push(['t_issuer', 'ILIKE', '%'.$issuer.'%']);
            }

            if (!empty(array_get($attributes['multi_search'], 't_agent_name'))) {
                $where->push(['t_agent_name', 'ILIKE', '%'.array_get($attributes['multi_search'], 't_agent_name').'%']);
            }
        }

        if ($attributes['csv']) {
            $transactions = $this->transactionRepository->exportTransactionsToCsv(
                $where,
                $dateConditionTransaction,
                $dateConditionRefund,
                $attributes['order_field'],
                $attributes['order']
            );
        } else {
            $transactions = $this->transactionRepository->listTransactions(
                $where,
                $dateConditionTransaction,
                $dateConditionRefund,
                $attributes['limit'],
                $attributes['order_field'],
                $attributes['order']
            );
            if (!empty($transactions['data'])) {
                $summary = $this->listTransactionsSkuSummary(
                    $where,
                    $dateConditionTransaction,
                    $dateConditionRefund
                );
            }
        }

        if (empty($transactions)) {
            return [];
        }

        foreach ($transactions['data'] as $k => $details) {
            $transactions['data'][$k]['country'] = getCountryName($details['country_code']);
            $transactions['data'][$k]['city'] = getCityName($details['city_code']);
            $transactions['data'][$k]['receipt_url'] = getFilePath($details, $details['t_invoice_storage']);
            $transactions['data'][$k]['amount'] = number_format((float) $details['amount'], 2);
            $transactions['data'][$k]['amount_without_tax'] = number_format((float) $details['amount_without_tax'], 2);
        }

        if ($attributes['order_field'] == 'country' || $attributes['order_field'] == 'city') {
            $attributes['order'] = $attributes['order'] == 'asc' ? SORT_ASC : SORT_DESC;

            $keyValues = array_column($transactions['data'], $attributes['order_field']);
            array_multisort($keyValues, $attributes['order'], $transactions['data']);
        }

        return [
            'total' => array_get($transactions, 'total', 0),
            'data' => array_get($transactions, 'data', []),
            'current_page' => array_get($transactions, 'current_page', 1),
            'summary' => $summary ?? [],
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
            'Date of transaction/refund',
            'Transaction ID',
            'Group ID',
            'Basket type',
            'SKU',
            'Payment type',
            'Gateway transaction ID',
            'Currency',
            'Amount (without tax)',
            'VAT',
            'Amount (with tax)',
            'Quantity',
            'Agent',
        ];
        $fields = [
            't_client',
            'country',
            'city',
            'modification_date',
            't_transaction_id',
            't_xref_fg_id',
            't_service',
            'ti_fee_type',
            't_payment_method',
            't_gateway_transaction_id',
            't_currency',
            'amount_without_tax',
            'ti_vat',
            'amount',
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

    /**
     * @param array $transaction
     *
     * @return void
     */
    public function confirmTransaction(array $transaction): void
    {
        $gateway = 'free';
        $paymentMethod = 'free';
        if (!empty($transaction['t_agent_name']) && !empty($transaction['t_payment_method'])) {
            $gateway = 'paybank';
            $paymentMethod = $transaction['t_payment_method'];
        }

        if ($transaction && !empty($transaction['t_items']) && !empty($transaction['t_xref_fg_id'])) {
            $workflowServiceSyncStatus = $this->syncTransactionToWorkflow($transaction);
            if (!empty($workflowServiceSyncStatus['error_msg'])) {
                Log::error(
                    'Transaction ERROR: transaction sync to workflow service '.
                        $transaction['t_transaction_id'].' failed, because: '.
                        json_encode($workflowServiceSyncStatus, 256)
                );
            }

            $ecommerceSyncStatus = $this->syncTransactionToEcommerce($transaction, 'PAID');
            if (!empty($ecommerceSyncStatus['error_msg'])) {
                Log::error(
                    'Transaction ERROR: transaction sync to ecommerce '.
                        $transaction['t_transaction_id'].' failed, because: '.
                        json_encode($ecommerceSyncStatus, 256)
                );
            }
        }

        $this->transactionRepository->updateById(
            $transaction['t_id'],
            [
                't_status' => 'done',
                't_gateway' => $gateway,
                't_payment_method' => $paymentMethod,
            ]
        );

        if ($this->isVersion(1, $transaction['t_issuer'], 'invoice')) {
            InvoiceService::generateInvoice($transaction);
        } else {
            dispatch(new InvoiceMailJob($transaction, 'tlspay_email_invoice'))
                ->onConnection('tlspay_invoice_queue')->onQueue('tlspay_invoice_queue');
        }

        $result = [
            'is_success' => 'ok',
            'orderid' => $transaction['t_transaction_id'],
            'issuer' => $transaction['t_issuer'],
            'amount' => $transaction['t_amount'],
            'message' => 'Transaction OK: transaction has been confirmed.',
        ];

        $this->transactionLogsService->create([
            'tl_xref_transaction_id' => $transaction['t_transaction_id'],
            'tl_content' => json_encode($result),
        ]);
    }

    /**
     * @param array  $transaction
     * @param string $paymentGateway
     * @param string $agentName
     * @param string $forcePayForNotOnlinePaymentAvs
     *
     * @return array
     */
    public function syncTransaction(
        array $transaction,
        string $paymentGateway,
        string $agentName = '',
        string $forcePayForNotOnlinePaymentAvs = ''
    ): array {
        $client = $transaction['t_client'];

        $formGroupInfo = $this->formGroupService->fetch($transaction['t_xref_fg_id'], $client);
        if (empty($formGroupInfo)) {
            return [
                'status' => 'error',
                'error_msg' => 'form_group_not_found',
            ];
        }

        $data = [
            'gateway' => $paymentGateway,
            'u_id' => !empty($formGroupInfo['fg_xref_u_id']) ? $formGroupInfo['fg_xref_u_id'] : 0,
            't_items' => $transaction['t_items'],
            't_transaction_id' => $transaction['t_transaction_id'],
            't_issuer' => $transaction['t_issuer'],
            't_currency' => $transaction['t_currency'],
        ];

        if (!empty($agentName)) {
            $data['agent_name'] = $agentName;
        }

        if ($forcePayForNotOnlinePaymentAvs === 'yes') {
            $data['force_pay_for_not_online_payment_avs'] = $forcePayForNotOnlinePaymentAvs;
        }

        Log::info('TransactionService syncTransaction start');

        try {
            dispatch(new TransactionSyncJob($client, $data))
                ->onConnection('tlscontact_transaction_sync_queue')
                ->onQueue('tlscontact_transaction_sync_queue');

            Log::info('TransactionService syncTransaction:dispatch');

            return [
                'error_msg' => [],
            ];
        } catch (\Exception $e) {
            Log::info('TransactionService syncTransaction dispatch error_msg:'.$e->getMessage());

            return [
                'status' => 'error',
                'error_msg' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param array $transaction
     *
     * @return array
     */
    public function syncTransactionToWorkflow(array $transaction): array
    {
        $client = $transaction['t_client'];
        $location = substr($transaction['t_issuer'], 0, 5);
        $fgId = $transaction['t_xref_fg_id'];
        $data = $this->createWorkflowPayload($this->getTransaction($transaction['t_id']));

        Log::info('TransactionService syncTransactionToWorkflow start: '.$fgId);

        try {
            dispatch(new TransactionSyncToWorkflowJob($client, $location, $data))
                ->onConnection('workflow_transaction_sync_queue')
                ->onQueue('workflow_transaction_sync_queue');

            Log::info('TransactionService syncTransactionToWorkflow dispatch: '.$fgId);

            return [
                'error_msg' => [],
            ];
        } catch (\Exception $e) {
            Log::info('TransactionService syncTransactionToWorkflow dispatch: '.$fgId.' - error_msg:'.$e->getMessage());

            return [
                'status' => 'error',
                'error_msg' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param array  $transaction
     * @param string $paymentStatus
     *
     * @return array
     */
    public function syncTransactionToEcommerce(array $transaction, string $paymentStatus): array
    {
        $fg_id = $transaction['t_xref_fg_id'];
        $data = $this->createEcommercePayload($transaction, $paymentStatus);

        Log::info('TransactionService syncTransactionToEcommerce start: '.$fg_id);

        try {
            /** @var QueueService $queueService */
            $queueService = app()->make('App\Services\QueueService');
            $queueService->syncTransactionToEcommerce($fg_id, $data);

            return [
                'error_msg' => [],
            ];
        } catch (\Exception $e) {
            Log::info('TransactionService syncTransactionToEcommerce sync: '.$fg_id.' - error_code:'.$e->getCode().' - error_msg:'.$e->getMessage());

            if (in_array((int) $e->getCode(), [404, 408])) {
                dispatch(new TransactionSyncToEcommerceJob($fg_id, $data))
                    ->onConnection('ecommerce_transaction_sync_queue')
                    ->onQueue('ecommerce_transaction_sync_queue');

                Log::info('TransactionService syncTransactionToEcommerce dispatch: '.$fg_id);
            }

            return [
                'status' => 'error',
                'error_msg' => $e->getMessage(),
            ];
        }
    }

    /**
     * Close the transaction if it is expired.
     *
     * This method marks a transaction 't_status' as closed and sets 't_tech_modification' to the current time.
     *
     * @param \App\Models\Transactions $transaction
     * @param mixed                    $now
     *
     * @return bool
     */
    protected function closeTransaction($transaction, $now): bool
    {
        return (bool) $this->transactionRepository->update(
            ['t_id' => $transaction->t_id],
            ['t_status' => 'close', 't_tech_modification' => $now]
        );
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
                    'ti_price_rule' => $sku['price_rule'] ?? null,
                ];
                // agent receipt is used
                if (isset($sku['quantity'])) {
                    $res['ti_quantity'] = $sku['quantity'];
                }
                if (filled($add_field)) {
                    $res = $res + $add_field;
                }

                if (!empty($sku['product_name'])) {
                    $res['ti_fee_name'] = trim($sku['product_name']);
                }

                $res['ti_label'] = null;
                if (!empty($sku['label'])) {
                    $res['ti_label'] = trim($sku['label']);
                }

                $res['ti_tag'] = null;
                if (!empty($sku['tags'])) {
                    $res['ti_tag'] = implode(',', $sku['tags']);
                }

                $response[] = $res;
            }
        }

        return $response;
    }

    /**
     * Check if the transaction items are changed.
     *
     * @param array $items
     * @param array $transItems
     *
     * @return bool
     */
    private function areItemsChanged(&$items, $transItems): bool
    {
        $isChanged = false;
        if (count($items) !== count($transItems)) {
            $isChanged = true;
        } else {
            foreach ($items as $key => $item) {
                if ($this->transactionItemsService->fetch($item)->isEmpty()) {
                    $isChanged = true;

                    break;
                }
                unset($items[$key]);
            }
        }

        return $isChanged;
    }

    /**
     * Check if a transaction can be closed.
     *
     * @param \App\Models\Transactions $transaction
     * @param mixed                    $now
     *
     * @return bool
     */
    private function canTransactionBeClosed($transaction, $now): bool
    {
        $isTransactionExpired = !is_null($transaction->t_expiration) && $now->gt($transaction->t_expiration);
        $isGatewayExpired = !is_null($transaction->t_gateway_expiration) && $now->gt($transaction->t_gateway_expiration);

        return $isTransactionExpired || $isGatewayExpired;
    }

    /**
     * @param Collection $where
     * @param Collection $dateConditionTransaction
     * @param Collection $dateConditionRefund
     *
     * @return array
     */
    private function listTransactionsSkuSummary(
        Collection $where,
        Collection $dateConditionTransaction,
        Collection $dateConditionRefund
    ): array {
        $data = $this->transactionRepository->listTransactionsSkuSummary(
            $where,
            $dateConditionTransaction,
            $dateConditionRefund
        );
        if (!empty($data)) {
            foreach ($data as $skuDetails) {
                $sku = $skuDetails['sku'];
                $currency = $skuDetails['currency'];
                $paymentMethod = $skuDetails['payment_method'];

                if (!isset($skuData[$currency][$sku][$paymentMethod])) {
                    $skuData[$currency][$sku][$paymentMethod] = 0;
                }
                $skuData[$currency][$sku][$paymentMethod] += (float) $skuDetails['amount'];
                if (!isset($totalByPaymentMethod[$currency][$paymentMethod])) {
                    $totalByPaymentMethod[$currency][$paymentMethod] = 0;
                }
                $totalByPaymentMethod[$currency][$paymentMethod] += (float) $skuDetails['amount'];
                if (!isset($totalAmount[$currency])) {
                    $totalAmount[$currency] = 0;
                }
                $totalAmount[$currency] += (float) $skuDetails['amount'];
            }
            foreach ($skuData as $currency => $skuList) {
                $skuSummary = $this->skuSummary($skuList);

                $summary[] = [
                    'currency' => $currency,
                    'cash-amount-total' => number_format((float) ($totalByPaymentMethod[$currency]['cash'] ?? 0), 2),
                    'card-amount-total' => number_format((float) ($totalByPaymentMethod[$currency]['card'] ?? 0), 2),
                    'online-amount-total' => number_format((float) ($totalByPaymentMethod[$currency]['online'] ?? 0), 2),
                    'amount-total' => number_format((float) ($totalAmount[$currency] ?? 0), 2),
                    'skus' => $skuSummary,
                ];
            }
        }

        return $summary ?? [];
    }

    /**
     * @param array $skuList
     *
     * @return array
     */
    private function skuSummary(array $skuList): array
    {
        foreach ($skuList as $sku => $skuDetails) {
            $totalAmount = 0;
            $summary = [];

            foreach ($skuDetails as $paymentMethod => $amount) {
                $summary[] = [
                    'payment-type' => $paymentMethod,
                    'amount' => number_format((float) $amount, 2),
                ];
                $totalAmount += $amount;
            }
            $skus[] = [
                'sku' => $sku,
                'amount-total' => number_format((float) $totalAmount, 2),
                'summary' => $summary,
            ];
        }

        return $skus ?? [];
    }

    /**
     * @param array $transaction
     *
     * @return array
     */
    private function createWorkflowPayload(array $transaction): array
    {
        foreach ($transaction['t_items'] as $items) {
            foreach ($items['skus'] as $sku) {
                $orderDetails[] = array_merge(
                    [
                        'f_id' => $items['f_id'],
                        'currency' => $transaction['t_currency'],
                        'name' => $sku['product_name'],
                        'label' => $sku['label'] ?? '',
                        'stamp' => $sku['tag'] ?? '',
                    ],
                    Arr::only($sku, ['sku', 'vat', 'quantity', 'price'])
                );
            }
        }

        return [
            'client' => $transaction['t_client'],
            'location' => substr($transaction['t_issuer'], 0, 5),
            'fg_id' => $transaction['t_xref_fg_id'],
            'date' => $transaction['t_appointment_date'],
            'time' => substr($transaction['t_appointment_time'], 0, 5),
            'order_id' => $transaction['t_transaction_id'],
            'payment_type' => $transaction['t_service'],
            'order_details' => $orderDetails,
            'payment_provider' => $transaction['t_gateway'],
            'timestamp' => $transaction['t_tech_modification'],
        ];
    }

    /**
     * @param array  $transaction
     * @param string $paymentStatus
     *
     * @return array
     */
    private function createEcommercePayload(array $transaction, string $paymentStatus): array
    {
        $filteredItems = [];

        foreach ($transaction['t_items'] as $key => $items) {
            $filteredItems[$key] = [
                'f_id' => $items['f_id'],
            ];

            foreach ($items['skus'] as $sku) {
                $filteredItems[$key]['skus'][] = Arr::only($sku, ['sku', 'quantity', 'price']);
            }
        }

        return [
            't_id' => $transaction['t_id'],
            'paymentStatus' => $paymentStatus,
            'items' => $filteredItems,
        ];
    }
}
