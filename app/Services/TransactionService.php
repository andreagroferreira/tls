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
    )
    {
        $this->transactionRepository = $transactionRepository;
        $this->dbConnectionService = $dbConnectionService;
        $this->transactionItemsService = $transactionItemsService;
        $this->transactionRepository->setConnection($this->dbConnectionService->getConnection());
    }

    public function updateById($t_id, $attributes) {
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
            $this->transactionRepository->update(['t_id' => $transaction->t_id], ['t_status' => 'close', 't_tech_modification' => $now]);
            return true;
        }

        $is_change = false;
        $res = $this->convertItemsFieldToArray($transaction->t_transaction_id, $attributes['items'], ['ti_tech_deleted' => false]);
        foreach ($res as $key => $item) {
            if ($this->transactionItemsService->fetch($item)->isEmpty()) {
                $is_change = true;
                break;
            } else {
                unset($res[$key]);
            }
        }
        if ($is_change || filled($res)) {
            $this->transactionRepository->update(['t_id' => $transaction->t_id], ['t_status' => 'close', 't_tech_modification' => $now]);
            return true;
        }

        return ['t_id' => $transaction->t_id, 'expire' => $transaction->t_expiration];
    }

    public function create($attributes)
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
            't_expiration' => Carbon::parse($this->dbConnectionService->getDbNowTime())->addMinutes(config('payment_gateway.expiration_minutes')),
        ];
        $transaction_data['t_transaction_id'] = $this->generateTransactionId($transaction_data['t_id'], $transaction_data['t_issuer']);

        $db_connection = DB::connection($this->dbConnectionService->getConnection());
        $db_connection->beginTransaction();

        try {

            $transaction = $this->transactionRepository->create($transaction_data);
            $this->transactionItemsService->createMany($this->convertItemsFieldToArray($transaction->t_transaction_id, $attributes['items']));

            $db_connection->commit();
        } catch (\Exception $e) {
            $db_connection->rollBack();
            return false;
        }

        return ['t_id' => $transaction->t_id, 'expire' => Carbon::parse($transaction->t_expiration)->toDateTimeString()];
    }

    protected function generateTransactionId($transaction_id_seq, $issuer)
    {
        $environment = env('APPLICATION_ENV') == 'prod' ? '' : strtoupper(env('APPLICATION_ENV')) . date('Ymd') . '-';
        $project = env('PROJECT') ? env('PROJECT') . '-' : '';
        return $project . $environment . $issuer . '-' . str_pad($transaction_id_seq, 10, '0', STR_PAD_LEFT);
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
                if (filled($add_field)) {
                    $res = $res + $add_field;
                }

                $response[] = $res;
            }
        }

        return $response;
    }

    public function update($transaction_id, $attributes) {
        return $this->transactionRepository->update($transaction_id, $attributes);
    }

    public function getTransaction($t_id): array
    {
        $transaction = $this->transactionRepository->fetch(['t_id' => $t_id])->first();
        if(empty($transaction)) {
            return [];
        }
        $transaction = $transaction->toArray();
        $transaction_id = $transaction['t_transaction_id'];
        $transaction_items = $this->transactionItemsService->fetchItemsByTransactionId($transaction_id)->toArray();
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
        if($transaction->isEmpty()) {
            return [];
        }
        $t_id = $transaction->first()->t_id;
        return $this->getTransaction($t_id);
    }

    public function fetchByTransactionId($transaction_id) {
        return $this->transactionRepository->findBy([
            't_id' => $transaction_id,
            't_tech_deleted' => false,
        ])->first();
    }

    public function getDbNowTime() {
        return Carbon::parse($this->dbConnectionService->getDbNowTime())->getTimestamp();
    }

    public function getDbTimeZone() {
        return Carbon::parse($this->dbConnectionService->getDbNowTime())->getTimezone()->toRegionName();
    }
}
