<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTransactionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->create('transaction_items', function (Blueprint $table) {
            $table->bigIncrements('ti_id');
            $table->bigInteger('ti_xref_f_id')->index()->comment('tlsconnect f_id');
            $table->string('ti_xref_transaction_id')->index()->comment('the referenced t_transaction_id');
            $table->string('ti_fee_type')->comment('sku');
            $table->float('ti_vat', 10, 0);
            $table->float('ti_amount', 10, 0)->comment('price');
            $table->timestamp('ti_tech_creation')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('ti_tech_modification')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->boolean('ti_tech_deleted')->default(0);
        });

        DB::connection('deploy_payment_pgsql')->statement("ALTER TABLE transaction_items OWNER TO postgres;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->dropIfExists('transaction_items');
    }
}
