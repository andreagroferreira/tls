<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAgentNameToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->table('transactions', function (Blueprint $table) {
            $table->string('t_agent_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->table('transactions', function (Blueprint $table) {
            $table->dropColumn('t_agent_name');
        });
    }
}
