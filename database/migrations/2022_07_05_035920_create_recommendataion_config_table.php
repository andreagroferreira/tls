<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecommendataionConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->create('recommendataion_config', function (Blueprint $table) {
            $table->bigIncrements('rc_id');
            $table->string('rc_file_name')->nullable(false);
            $table->string('rc_uploaded_by')->nullable(false);
            $table->text('rc_content')->nullable(false);
            $table->bigInteger('rc_file_size')->nullable(false);
            $table->string('rc_comment')->nullable(true);
            $table->boolean('rc_tech_deleted')->default(0);
            $table->timestamp('rc_tech_creation')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        DB::connection('deploy_payment_pgsql')->statement('ALTER TABLE recommendataion_config OWNER TO common;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recommendataion_config');
    }
}
