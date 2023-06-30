<?php

use Illuminate\Database\Migrations\Migration;
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
        try {
            Schema::connection('deploy_payment_pgsql')->table('profiles', function ($table) {
                $table->dropUnique('f_id_profile_unique_index');
            });
        } catch (\Exception $exception) {
            $message = 'constraint "f_id_profile_unique_index" of relation "profiles" does not exist';
            if ($exception->getCode() === '42704' && str_contains($exception->getMessage(), $message)) {
                return;
            }

            throw $exception;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->table('profiles', function ($table) {
            $table->unique(['p_xref_f_id', 'p_profile'], 'f_id_profile_unique_index');
        });
    }
}
