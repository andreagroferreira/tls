<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->create('refunds', function (Blueprint $table) {
            $table->bigIncrements('r_id');
            $table->string('r_issuer', 10);
            $table->string('r_reason_type');
            $table->string('r_status')->comment('pending, closed, approved, confirmed, refunded, done');
            $table->timestamp('r_appointment_date')->nullable();
            $table->timestamp('r_tech_creation')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('r_tech_modification')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->boolean('r_tech_deleted')->default(0);
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
        Schema::connection('deploy_payment_pgsql')->dropIfExists('refunds');
    }
}
