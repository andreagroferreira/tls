<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->table('transactions', function (Blueprint $table) {
            $table->bigInteger('t_xref_pa_id')->nullable()->index()->comment('payment_accounts pa_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->table('transactions', function($table)
        {
            $table->dropColumn('t_xref_pa_id');
        });
    }
}
