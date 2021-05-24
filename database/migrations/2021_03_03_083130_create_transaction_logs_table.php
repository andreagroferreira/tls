<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTransactionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->create('transaction_logs', function (Blueprint $table) {
            $table->bigIncrements('tl_id');
            $table->string('tl_xref_transaction_id')->index();
            $table->text('tl_content');
            $table->timestamp('tl_tech_creation')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        DB::connection('deploy_payment_pgsql')->statement("ALTER TABLE transaction_logs OWNER TO postgres;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->dropIfExists('transaction_logs');
    }
}
