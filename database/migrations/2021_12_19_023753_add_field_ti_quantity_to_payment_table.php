<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldTiQuantityToPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->table('transaction_items', function (Blueprint $table) {
            $table->bigInteger('ti_quantity')->nullable(true)->comment('avs quantity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->table('transaction_items', function (Blueprint $table) {
            $table->dropColumn('ti_quantity');
        });
    }
}
