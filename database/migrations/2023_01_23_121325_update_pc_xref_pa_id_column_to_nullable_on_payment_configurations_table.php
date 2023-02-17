<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdatePcXrefPaIdColumnToNullableOnPaymentConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->table('payment_configurations', function (Blueprint $table) {
            $table->integer('pc_xref_pa_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->table('payment_configurations', function (Blueprint $table) {
            $table->integer('pc_xref_pa_id')->nullable(false)->change();
        });
    }
}
