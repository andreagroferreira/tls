<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->create('transactions', function (Blueprint $table) {
            $table->bigIncrements('t_id');
            $table->bigInteger('t_xref_fg_id')->index()->comment('tlsconnect fg_id');
            $table->string('t_transaction_id')->comment('transaction id generate by payment system');
            $table->string('t_client', 10)->comment('the clients id');
            $table->string('t_issuer', 10)->comment('the issuer tag');
            $table->string('t_gateway_transaction_id')->comment('transaction id from gateway')->nullable();
            $table->string('t_gateway')->comment('payment gateway name')->nullable();
            $table->string('t_currency')->comment('the payment currency for this transaction');
            $table->string('t_status')->comment('the transaction status, it support pending,done,partial_refunded,refunded')->default('pending');
            $table->string('t_redirect_url')->comment('the tlsconnect website redirection url after payment done');
            $table->string('t_onerror_url');
            $table->string('t_reminder_url');
            $table->string('t_callback_url');
            $table->timestamp('t_expiration')->nullable();
            $table->timestamp('t_gateway_expiration')->nullable();
            $table->string('t_workflow');
            $table->timestamp('t_tech_creation')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('t_tech_modification')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->boolean('t_tech_deleted')->default(0);
        });

        DB::connection('deploy_payment_pgsql')->statement("ALTER TABLE transactions OWNER TO postgres;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->dropIfExists('transactions');
    }
}
