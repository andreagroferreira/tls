<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePaymentAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('payment_pgsql')->create('payment_accounts', function (Blueprint $table) {
            $table->bigIncrements('pa_id');
            $table->integer('pa_xref_psp_id')->index('payment_accounts_pa_xref_psp_id')->comment('the referenced psp_id');
            $table->string('pa_type');
            $table->string('pa_name');
            $table->text('pa_info');
            $table->boolean('pa_tech_deleted')->default(0);
            $table->timestamp('pa_tech_creation')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('pa_tech_modification')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
        DB::statement(
            "CREATE UNIQUE INDEX payment_accounts_unique_name
                    ON payment_accounts(pa_name)
                    WHERE pa_tech_deleted is false"
        );
        DB::connection('payment_pgsql')->statement("ALTER TABLE payment_accounts OWNER TO common;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_accounts');
    }
}
