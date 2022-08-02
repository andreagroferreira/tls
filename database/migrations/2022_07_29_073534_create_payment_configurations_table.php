<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePaymentConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->create('payment_configurations', function (Blueprint $table) {
            $table->bigIncrements('pc_id');
            $table->string('pc_xref_pa_id')->index('payment_configurations_pc_xref_pa_id')->comment('the referenced pa_id')->nullable();
            $table->string('pc_project');
            $table->string('pc_country');
            $table->string('pc_city');
            $table->string('pc_service')->comment('for gov service or tls service')->default('tls');
            $table->boolean('pc_tech_deleted')->default(0);
            $table->timestamp('pc_tech_creation')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('pc_tech_modification')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
        DB::connection('deploy_payment_pgsql')->statement("ALTER TABLE payment_configurations OWNER TO common;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_configurations');
    }
}
