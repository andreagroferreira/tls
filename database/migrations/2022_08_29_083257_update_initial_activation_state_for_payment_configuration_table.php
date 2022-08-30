<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateInitialActivationStateForPaymentConfigurationTable extends Migration
{
    private $default_config = 'pay_later';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDefaultConnection() == 'deploy_payment_pgsql') {
            $active_status = env('APP_ENV') != 'production' ? 'sandbox' : 'prod';
            DB::beginTransaction();
            try {
                $accounts = DB::table('payment_accounts')
                    ->whereIn('pa_type', [$active_status, $this->default_config])
                    ->get()->toArray();
                if (!empty($accounts)) {
                    $active_accounts = json_decode(json_encode($accounts), true);
                    $active_pa_ids   = array_column($active_accounts, 'pa_id');
                    DB::table('payment_configurations')
                        ->whereIn('pc_xref_pa_id', $active_pa_ids)
                        ->update(['pc_is_actived' => true]);
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
