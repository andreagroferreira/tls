<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultPayInVac extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDefaultConnection() == 'deploy_payment_pgsql') {
            $condition = [
                'psp_code' => 'pay_later',
                'psp_name' => 'pay_later'
            ];
            $clients = ['be', 'ch', 'de', 'pl', 'fr', 'hmpo_uk', 'leg_be', 'leg_de'];
            DB::beginTransaction();
            try {
                $payment_service_info = DB::table('payment_service_providers')->where($condition)->first();
                $payment_service_psp_id = isset($payment_service_info->psp_id) ? $payment_service_info->psp_id : '';
                if (empty($payment_service_psp_id)) {
                    $payment_service_psp_id = DB::table('payment_service_providers')->insertGetId($condition, 'psp_id');
                }
                $condition_payment_accounts = [
                    'pa_xref_psp_id' => $payment_service_psp_id,
                    'pa_type' => 'pay_later',
                    'pa_name' => 'pay_later',
                    'pa_info' => ''
                ];
                $payment_account_info = DB::table('payment_accounts')->where($condition_payment_accounts)->first();
                $payment_account_pa_id = isset($payment_account_info->pa_id) ? $payment_account_info->pa_id : '';
                if (empty($payment_account_pa_id)) {
                    $payment_account_pa_id = DB::table('payment_accounts')->insertGetId($condition_payment_accounts, 'pa_id');
                }
                foreach ($clients as $client) {
                    $condition_payment_configurations = [
                        'pc_project' => $client,
                        'pc_country' => 'all',
                        'pc_city' => 'all',
                        'pc_service' => 'tls'
                    ];
                    $payment_configurations = DB::table('payment_configurations')->where($condition_payment_configurations)->first();
                    if (empty($payment_configurations)) {
                        $condition_payment_configurations['pc_xref_pa_id'] = $payment_account_pa_id;
                        $condition_payment_configurations['pc_is_actived'] = true;
                        DB::table('payment_configurations')->insert($condition_payment_configurations);
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw new LogicException($e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
