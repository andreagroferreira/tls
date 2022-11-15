<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameColumnForPaymentConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->table('payment_configurations', function (Blueprint $table) {
            $table->renameColumn('pc_is_actived', 'pc_is_active');
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
            $table->renameColumn('pc_is_active', 'pc_is_actived');
        });
    }
}
