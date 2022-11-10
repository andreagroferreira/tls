<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefundLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->create('refund_logs', function (Blueprint $table) {
            $table->bigIncrements('rl_id');
            $table->bigInteger('rl_xref_r_id')->index();
            $table->bigInteger('rl_xref_ri_id')->index()->nullable();
            $table->string('rl_type')->comment('status_change, file_request, email_sent');
            $table->text('rl_description');
            $table->string('rl_agent');
            $table->timestamp('rl_tech_creation')->useCurrent();
            $table->timestamp('rl_tech_modification')->useCurrent();
            $table->boolean('rl_tech_deleted')->default(0);
        });

        DB::connection('deploy_payment_pgsql')->statement('ALTER TABLE refund_logs OWNER TO common;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->dropIfExists('refund_logs');
    }
}
