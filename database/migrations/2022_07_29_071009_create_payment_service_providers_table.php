<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePaymentServiceProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('payment_pgsql')->create('payment_service_providers', function (Blueprint $table) {
            $table->bigIncrements('psp_id');
            $table->string('psp_code');
            $table->string('psp_name');
            $table->boolean('psp_tech_deleted')->default(0);
            $table->timestamp('psp_tech_creation')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('psp_tech_modification')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        DB::connection('payment_pgsql')->statement("ALTER TABLE payment_service_providers OWNER TO common;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_service_providers');
    }
}
