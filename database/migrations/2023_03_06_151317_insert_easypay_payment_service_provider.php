<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InsertEasypayPaymentServiceProvider extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDefaultConnection() === 'deploy_payment_pgsql') {
            DB::beginTransaction();

            try {
                DB::table('payment_service_providers')->insert([
                    'psp_code' => 'easypay',
                    'psp_name' => 'EasyPay',
                ]);
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
