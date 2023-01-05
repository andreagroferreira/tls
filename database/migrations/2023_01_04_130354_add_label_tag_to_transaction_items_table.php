<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLabelTagToTransactionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->table('transaction_items', function (Blueprint $table) {
            $table->string('ti_label')->nullable();
            $table->string('ti_tag')->nullable();
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
            $table->dropColumn('ti_label');
            $table->dropColumn('ti_tag');
        });
    }
}
