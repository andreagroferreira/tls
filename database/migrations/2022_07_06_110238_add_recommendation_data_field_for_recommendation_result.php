<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRecommendationDataFieldForRecommendationResult extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->table('recommendation_result', function (Blueprint $table) {
            $table->string('rr_profile')->nullable();
            $table->string('rr_issuer')->nullable();
            $table->string('rr_price')->nullable();
            $table->string('rr_currency')->nullable();
            $table->string('rr_service_script')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->table('recommendation_result', function($table)
        {
            $table->dropColumn('rr_profile');
            $table->dropColumn('rr_issuer');
            $table->dropColumn('rr_price');
            $table->dropColumn('rr_currency');
            $table->dropColumn('rr_service_script');
        });
    }
}
