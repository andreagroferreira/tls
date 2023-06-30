<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropResultColumnsTransferTransactionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->table('transfer_table_transaction_items', function (Blueprint $table) {
            $table->dropColumn('result_migration_payment');
            $table->dropColumn('result_migration_ecommerce');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->table('transfer_table_transaction_items', function (Blueprint $table) {
            $table->text('result_migration_payment')->nullable();
            $table->text('result_migration_ecommerce')->nullable();
        });
    }
}
