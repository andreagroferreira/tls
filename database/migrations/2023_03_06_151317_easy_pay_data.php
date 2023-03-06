<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EasyPayData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDefaultConnection() === 'deploy_payment_pgsql') {
            DB::unprepared('TRUNCATE TABLE payment_service_providers RESTART IDENTITY CASCADE');

            $payment_gateways = [
                'easy_pay' => 'EasyPay',
            ];

            DB::beginTransaction();

            try {
                foreach ($payment_gateways as $key => $gateway) {
                    $payment_service = DB::table('payment_service_providers')->where(['psp_code' => $gateway])->first();
                    $condition = [
                        'psp_code' => $key,
                        'psp_name' => $gateway,
                    ];
                    if (empty($payment_service)) {
                        DB::table('payment_service_providers')->insert($condition);
                    } else {
                        DB::table('payment_service_providers')->where('psp_id', '=', $payment_service->psp_id)->update($condition);
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
        //
    }
}
