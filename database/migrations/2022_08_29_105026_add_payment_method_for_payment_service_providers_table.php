<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddPaymentMethodForPaymentServiceProvidersTable extends Migration
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
                'psp_code' => 'cybersource',
                'psp_name' => 'cybersource pay'
            ];
            DB::beginTransaction();
            try {
                $payment_service = DB::table('payment_service_providers')->where($condition)->first();
                if (empty($payment_service)) {
                    DB::table('payment_service_providers')->insert($condition);
                } else {
                    DB::table('payment_service_providers')->where('psp_id', '=', $payment_service->psp_id)->update($condition);
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
