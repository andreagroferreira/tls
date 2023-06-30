<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeatureVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->create('feature_versions', function (Blueprint $table) {
            $table->bigIncrements('fv_id');
            $table->string('fv_type');
            $table->smallInteger('fv_version');
        });

        DB::connection('deploy_payment_pgsql')->statement('ALTER TABLE feature_versions OWNER TO common;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->dropIfExists('feature_versions');
    }
}
