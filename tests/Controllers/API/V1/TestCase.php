<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Support\Facades\DB;

abstract class TestCase extends \TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpConnections();
        $this->runDatabaseMigrations();
    }

    public function setUpConnections()
    {
        config(['database.connections.payment_pgsql' => config('database.connections.unit_test_payment_pgsql')]);
        config(['database.connections.deploy_payment_pgsql' => config('database.connections.unit_test_payment_pgsql')]);
    }

    public function runDatabaseMigrations()
    {
        $db_connection = DB::connection('unit_test_pgsql');
        $database = config('database.connections.unit_test_payment_pgsql.database');
        if ($db_connection->table('pg_database')->whereRaw("datname='$database'")->count() === 0) {
            $db_connection->statement("CREATE DATABASE $database");
        }

        $this->artisan('migrate:refresh', ['--path' => 'database/migrations', '--database' => 'unit_test_payment_pgsql', '--force' => true]);
    }

    public function getDbNowTime()
    {
        return DB::connection('unit_test_payment_pgsql')
            ->table('pg_database')
            ->selectRaw('now()')
            ->first()
            ->now;
    }

    public function generateTransaction($params = [])
    {
        if (blank($params)) {
            $params = [
                't_xref_fg_id' => 10000,
                't_transaction_id' => str_random(10),
                't_client' => 'be',
                't_issuer' => 'dzALG2be',
                't_gateway_transaction_id' => str_random(10),
                't_gateway' => 'cmi',
                't_currency' => 'MAD',
                't_status' => 'pending',
                't_redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr',
                't_onerror_url' => 'onError_tlsweb_url?lang=fr-fr',
                't_reminder_url' => 'callback_to_send_reminder?lang=fr-fr',
                't_callback_url' => 'receipt_url/{fg_id}?lang=fr-fr',
                't_workflow' => 'vac'
            ];
        }

        $db_connection = DB::connection('unit_test_payment_pgsql')->table('transactions');
        $t_id = $db_connection->insertGetId($params, 't_id');
        return $db_connection->where('t_id', $t_id)->first();
    }

    public function generateTransactionItems($transaction_id = '', $params = [])
    {
        if (blank($params)) {
            if (blank($transaction_id)) {
                $transaction = $this->generateTransaction();
                $transaction_id = $transaction->t_transaction_id;
            }
            $params = [
                'ti_xref_f_id' => 10001,
                'ti_xref_transaction_id' => $transaction_id,
                'ti_fee_type' => 1,
                'ti_vat' => 1,
                'ti_amount' => 1,
            ];
        }

        $db_connection = DB::connection('unit_test_payment_pgsql')->table('transaction_items');
        $item_id = $db_connection->insertGetId($params, 'ti_id');
        return $db_connection->where('ti_id', $item_id)->first();
    }

    public function updateTable($table, $where, $update)
    {
        return DB::connection('unit_test_payment_pgsql')
            ->table($table)
            ->where($where)
            ->update($update);
    }

    public function generateRcd($params = [])
    {
        if (blank($params)) {
            $params = [
                'rr_xref_f_id' => 10000,
                'rr_agent' => 'test.test',
                'rr_sku' => 'COURIER',
                'rr_result' => 'accept'
            ];
        }

        $db_connection = DB::connection('unit_test_payment_pgsql')->table('recommendation_result');
        $rr_id = $db_connection->insertGetId($params, 'rr_id');
        return $db_connection->where('rr_id', $rr_id)->first();
    }
}


