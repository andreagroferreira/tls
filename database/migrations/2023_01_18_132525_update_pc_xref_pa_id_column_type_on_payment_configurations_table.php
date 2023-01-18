<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdatePcXrefPaIdColumnTypeOnPaymentConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->table('payment_configurations', function (Blueprint $table) {
            DB::statement('ALTER TABLE payment_configurations ALTER COLUMN
                  pc_xref_pa_id TYPE integer USING (pc_xref_pa_id)::integer');
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
            $table->string('pc_xref_pa_id')->change();
        });
    }
}
