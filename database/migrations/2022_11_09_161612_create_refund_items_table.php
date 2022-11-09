<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefundItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->create('refund_items', function (Blueprint $table) {
            $table->bigIncrements('ri_id');
            $table->bigInteger('ri_xref_rr_id')->index();
            $table->bigInteger('ri_xref_ti_id')->index();
            $table->smallInteger('ri_quantity');
            $table->float('ri_amount', 10, 0)->nullable();
            $table->string('ri_reason_type');
            $table->string('ri_status')->comment('pending, approved, declined, done');
            $table->string('ri_invoice_path')->nullable();
            $table->timestamp('ri_tech_creation')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('ri_tech_modification')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->boolean('ri_tech_deleted')->default(0);
        });

        DB::connection('deploy_payment_pgsql')->statement('ALTER TABLE transactions OWNER TO common;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->dropIfExists('refund_items');
    }
}
