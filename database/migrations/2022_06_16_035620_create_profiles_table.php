<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('deploy_payment_pgsql')->create('profiles', function (Blueprint $table) {
            $table->bigIncrements('p_id');
            $table->bigInteger('p_xref_f_id')->index()->nullable(false);
            $table->string('p_profile')->nullable(false);
            $table->timestamp('p_tech_creation')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('p_tech_modification')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->boolean('p_tech_deleted')->default(0);
            $table->unique(['p_xref_f_id', 'p_profile'], 'f_id_profile_unique_index');
        });

        DB::connection('deploy_payment_pgsql')->statement("ALTER TABLE profiles OWNER TO common;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->dropIfExists('profiles');
    }
}
