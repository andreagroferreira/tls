<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameResultColumnTransferTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->table('transfer_table_transactions', function (Blueprint $table) {
            $table->renameColumn('result_migration_payment', 'result_migration');
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
        Schema::connection('deploy_payment_pgsql')->table('transfer_table_transactions', function (Blueprint $table) {
            $table->renameColumn('result_migration', 'result_migration_payment');
            $table->text('result_migration_ecommerce')->nullable();
        });
    }
}
