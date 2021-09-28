<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateRecommendationResultTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('unit_test_payment_pgsql')->create('recommendation_result', function (Blueprint $table) {
            $table->bigIncrements('rr_id');
            $table->bigInteger('rr_xref_f_id')->index()->comment('tlsconnect f_id');
            $table->string('rr_agent')->comment('agent login');
            $table->string('rr_sku', 100)->comment('sku in directus');
            $table->string('rr_result', 20)->comment('recommend result, "accept" or "deny"');
            $table->timestamp('rr_tech_creation')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('rr_tech_modification')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->boolean('rr_tech_deleted')->default(0);
            $table->bigInteger('rr_deleted_by')->nullable(true)->comment('agent id who deleted this line');
        });

        DB::connection('deploy_payment_pgsql')->statement("ALTER TABLE recommendation_result OWNER TO common;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('unit_test_payment_pgsql')->dropIfExists('recommendation_result');
    }
}
