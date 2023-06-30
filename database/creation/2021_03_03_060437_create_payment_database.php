<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreatePaymentDatabase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $database = config('database.connections.payment_pgsql.database');

        $db_connection = DB::connection('pgsql');
        if ($db_connection->table('pg_database')->whereRaw("datname='{$database}'")->count() === 0) {
            $db_connection->commit();
            $db_connection->statement('CREATE DATABASE "' . $database . '"');
            sleep(30);
        }

        DB::connection('deploy_payment_pgsql')->statement('ALTER database "' . $database . '" OWNER TO postgres;');
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
