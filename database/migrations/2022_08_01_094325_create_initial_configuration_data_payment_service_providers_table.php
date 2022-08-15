<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateInitialConfigurationDataPaymentServiceProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDefaultConnection() == 'deploy_payment_pgsql') {
            $payment_gateways = [
                'alipay', 'binga', 'bnp', 'clictopay', 'cmi', 'fawry', 'globaliris', 'paypal',
                'k-bank', 'payfort', 'paygate', 'paysoft', 'payu', 'switch', 'tingg', 'pay_later'
            ];
            DB::beginTransaction();
            try {
                foreach ($payment_gateways as $gateway) {
                    $payment_service = DB::table('payment_service_providers')->where(['psp_code' => $gateway])->first();
                    $condition       = [
                        'psp_code' => $gateway,
                        'psp_name' => ($gateway == 'pay_later') ? $gateway : $gateway . ' pay'
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

    }
}
