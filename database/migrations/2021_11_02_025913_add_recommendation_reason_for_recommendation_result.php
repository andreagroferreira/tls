<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRecommendationReasonForRecommendationResult extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->table('recommendation_result', function (Blueprint $table) {
            $table->string('rr_comment')->nullable(true)->comment('recommendation result comment');
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
            $table->dropColumn('rr_comment');
        });
    }
}
