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
        if ($db_connection->table('pg_database')->whereRaw("datname='{$database}'")->count() === 0) {
            $db_connection->statement("CREATE DATABASE {$database}");
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
                't_workflow' => 'vac',
                't_invoice_storage' => 'file-library',
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
                'rr_result' => 'accept',
            ];
        }

        $db_connection = DB::connection('unit_test_payment_pgsql')->table('recommendation_result');
        $rr_id = $db_connection->insertGetId($params, 'rr_id');

        return $db_connection->where('rr_id', $rr_id)->first();
    }

    public function getTransactions($where)
    {
        return DB::connection('unit_test_payment_pgsql')
            ->table('transactions')
            ->where($where)
            ->first();
    }

    public function getFormGroupResponse()
    {
        return [
            'code' => 200,
            'body' => [
                'fg_id' => 10001,
                'fg_xref_u_id' => 10003,
                'fg_name' => 'default group',
                'fg_tech_modification' => '2021-11-22T17:13:03.000000Z',
                'fg_tech_creation' => '2021-11-22T17:12:20.000000Z',
                'fg_tech_deleted' => false,
                'fg_csl' => null,
                'fg_visa_type' => null,
                'fg_trav_purpose' => null,
                'fg_departure_date_old' => null,
                'fg_return_date_old' => null,
                'fg_is_anonymised' => false,
                'fg_tour_leader_pax' => null,
                'fg_tour_leader_name' => null,
                'fg_note' => null,
                'fg_tour_leader_id' => null,
                'fg_student_id' => null,
                'fg_departure_date' => null,
                'fg_return_date' => null,
                'fg_return_arrival_time' => null,
                'fg_return_flight' => null,
                'fg_reporting_nb_ok' => null,
                'fg_reporting_nb_summoned' => null,
                'fg_tour_leader_mobile_phone' => null,
                'fg_application_path' => 'postal',
                'fg_day_until_next_appointment' => null,
                'fg_process' => 'schengen_vac',
                'fg_cai' => null,
                'fg_is_purged' => false,
                'u_id' => 10010,
                'u_xref_ug_id' => 38,
                'u_password' => 'b:$2y$10$ZhsVfoB4o1diVC.woUNcd.dhi6/1GI7V7Z64wDVm72WBlnKjYHs6W',
                'u_surname' => null,
                'u_givenname' => null,
                'u_tech_modification' => '2021-11-22 18:12:16.280758+01',
                'u_tech_creation' => '2021-11-22 18:12:16.280758+01',
                'u_tech_deleted' => false,
                'u_email' => 'user1-14420@test.fr',
                'u_tech_activated' => true,
                'u_tech_activation_code' => 'bfb03530b539658c4889657e219075a07517f03673016c24bad588e5f6120601',
                'u_tech_activation' => null,
                'u_role' => null,
                'u_pref_language' => null,
                'u_is_anonymised' => false,
                'u_nickname' => null,
                'u_csl' => null,
                'u_pref_homepage' => null,
                'u_login' => null,
                'u_password_last_modification' => '2021-11-22 18:12:16.280758+01',
                'u_change_password_when_login' => false,
                'u_relative_email' => 'user1-14420@test.fr',
                'u_logged_times' => 0,
                'u_last_login' => '2021-11-22 18:12:16.280758',
                'u_last_session' => '',
                'u_block_expiration' => null,
                'u_password_salt' => null,
                'u_is_purged' => false,
                'ug_type' => 'INDI',
                'ug_xref_i_tag' => 'gbLON2be',
                'ug_admin_type' => null,
                'ug_xref_gaug_id' => null,
                'ug_en' => 'Individual gbLON2be',
                'ug_id' => 38,
                'ug_tech_modification' => '2016-10-18 08:17:07.675123+01',
                'ug_tech_creation' => '2016-10-18 08:17:07.675123+01',
                'ug_tech_deleted' => false,
            ],
        ];
    }

    public function getPaymentAction()
    {
        return [
            'code' => 200,
            'body' => [
                'status' => 'ok',
                'error_msg' => [],
            ],
        ];
    }

    /**
     * @param array $params
     *
     * @return object
     */
    public function generateRefund(array $params = []): object
    {
        if (blank($params)) {
            $params = [
                'r_issuer' => 'dzALG2be',
                'r_reason_type' => 'other',
                'r_status' => 'done',
                'r_appointment_date' => '2022-11-14 12:00:00',
            ];
        }
        $db_connection = DB::connection('unit_test_payment_pgsql')->table('refunds');
        $r_id = $db_connection->insertGetId($params, 'r_id');

        return $db_connection->where('r_id', $r_id)->first();
    }

    /**
     * @param null|int $refundId
     * @param null|int $transactionItemId
     * @param array    $params
     *
     * @return object
     */
    public function generateRefundItems(
        int $refundId = null,
        int $transactionItemId = null,
        array $params = []
    ): object {
        if (blank($params)) {
            if (blank($refundId)) {
                $refund = $this->generateRefund();
                $refundId = $refund->r_id;
            }
            if (blank($transactionItemId)) {
                $transactionItem = $this->generateTransactionItems();
                $transactionItemId = $transactionItem->ti_id;
            }
            $params = [
                'ri_xref_r_id' => $refundId,
                'ri_xref_ti_id' => $transactionItemId,
                'ri_quantity' => 1,
                'ri_amount' => 450,
                'ri_reason_type' => 'other',
                'ri_status' => 'pending',
                'ri_invoice_path' => 'file-library',
            ];
        }

        $db_connection = DB::connection('unit_test_payment_pgsql')->table('refund_items');
        $item_id = $db_connection->insertGetId($params, 'ri_id');

        return $db_connection->where('ri_id', $item_id)->first();
    }

    public function generateConfigurationPaymentGatewayTypeTls($params = []): object
    {
        if (blank($params)) {
            $params = [
                'pc_project' => 'de',
                'pc_country' => 'eg',
                'pc_city' => 'CAI',
                'pc_service' => 'tls',
                'pc_is_active' => true,
            ];
        }

        $db_connection = DB::connection('unit_test_payment_pgsql')->table('payment_configurations');
        $pc_id = $db_connection->insertGetId($params, 'pc_id');

        return $db_connection->where('pc_id', $pc_id)->first();
    }

    public function generateConfigurationPaymentGatewayTypeGov($params = []): object
    {
        if (blank($params)) {
            $params = [
                'pc_project' => 'de',
                'pc_country' => 'eg',
                'pc_city' => 'ALY',
                'pc_service' => 'gov',
                'pc_is_active' => true,
            ];
        }

        $db_connection = DB::connection('unit_test_payment_pgsql')->table('payment_configurations');
        $pc_id = $db_connection->insertGetId($params, 'pc_id');

        return $db_connection->where('pc_id', $pc_id)->first();
    }

    public function generateConfigurationPaymentGatewayTypeBoth($params = []): object
    {
        if (blank($params)) {
            $params = [
                'pc_project' => 'de',
                'pc_country' => 'eg',
                'pc_city' => 'CAI',
                'pc_service' => 'gov',
                'pc_is_active' => true,
            ];
        }

        $db_connection = DB::connection('unit_test_payment_pgsql')->table('payment_configurations');
        $pc_id = $db_connection->insertGetId($params, 'pc_id');

        return $db_connection->where('pc_id', $pc_id)->first();
    }


}
