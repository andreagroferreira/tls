<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveProfilesTableUniqueIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('deploy_payment_pgsql', function ($table) {
            $table->dropUnique(['f_id_profile_unique_index']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deploy_payment_pgsql', function ($table) {
            $table->unique(['p_xref_f_id', 'p_profile'], 'f_id_profile_unique_index');
        });
    }
}
