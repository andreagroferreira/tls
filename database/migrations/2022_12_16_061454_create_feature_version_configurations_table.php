<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeatureVersionConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->create('feature_version_configurations', function (Blueprint $table) {
            $table->bigIncrements('fvc_id');
            $table->string('fvc_project');
            $table->string('fvc_country');
            $table->string('fvc_city');
            $table->bigInteger('fvc_xref_fv_id');
        });

        DB::connection('deploy_payment_pgsql')->statement('ALTER TABLE feature_version_configurations OWNER TO common;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->dropIfExists('feature_version_configurations');
    }
}
