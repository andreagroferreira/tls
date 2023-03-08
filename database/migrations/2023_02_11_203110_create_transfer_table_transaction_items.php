<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTransferTableTransactionItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->create('transfer_table_transaction_items', function (Blueprint $table) {
            $table->bigIncrements('tti_id');
            $table->bigInteger('ti_xref_f_id')->index()->comment('tlsconnect f_id');
            $table->string('ti_xref_transaction_id')->index()->comment('the referenced t_transaction_id');
            $table->string('ti_transaction_item')->comment('serialized json containing transaction item details');
            $table->timestamp('ti_tech_creation')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('ti_tech_modification')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->text('result_migration_payment')->nullable();
            $table->text('result_migration_ecommerce')->nullable();
        });

        DB::connection('deploy_payment_pgsql')->statement('ALTER TABLE transfer_table_transaction_items OWNER TO common;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transfer_table_transaction_item');
    }
}
