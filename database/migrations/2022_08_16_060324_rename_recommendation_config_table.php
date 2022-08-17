<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameRecommendationConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->rename('recommendataion_config', 'recommendation_config');
        Schema::connection('deploy_payment_pgsql')->table('recommendation_config', function (Blueprint $table) {
            $table->renameIndex('recommendataion_config_pkey', 'recommendation_config_pkey');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
