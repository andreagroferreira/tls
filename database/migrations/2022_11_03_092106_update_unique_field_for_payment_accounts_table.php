<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUniqueFieldForPaymentAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->table('payment_accounts', function (Blueprint $table) {
            $table->dropIndex('payment_accounts_unique_name');
            $table->unique(['pa_name', 'pa_type'], 'payment_accounts_unique_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->table('payment_accounts', function($table)
        {
            $table->dropIndex(['payment_accounts_unique_name']);
            $table->unique(['pa_name'], 'payment_accounts_unique_name');
        });
    }
}
