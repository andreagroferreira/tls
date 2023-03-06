<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTransferTableTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->create('transfer_table_transactions', function (Blueprint $table) {
            $table->bigIncrements('tt_id');
            $table->bigInteger('t_xref_fg_id')->index()->comment('tlsconnect fg_id');
            $table->string('t_transaction_id')->comment('transaction id generate by payment system');
            $table->string('t_client', 10)->comment('the clients id');
            $table->string('t_issuer', 10)->comment('the issuer tag');
            $table->string('t_gateway_transaction_id')->comment('transaction id from gateway')->nullable();
            $table->string('t_gateway')->comment('payment gateway name')->nullable();
            $table->string('t_currency')->comment('the payment currency for this transaction');
            $table->timestamp('t_tech_creation')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('t_tech_modification')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('t_agent_name')->nullable();
            $table->string('t_gateway_transaction_reference')->nullable();
            $table->string('f_visa_type')->nullable();
            $table->string('f_visa_sub_type')->nullable();
            $table->text('result_migration_payment')->nullable();
            $table->text('result_migration_ecommerce')->nullable();
        });

        DB::connection('deploy_payment_pgsql')->statement('ALTER TABLE transfer_table_transactions OWNER TO common;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transfer_table_transaction');
    }
}
