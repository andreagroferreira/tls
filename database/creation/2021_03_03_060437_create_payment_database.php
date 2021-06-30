<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        if ($db_connection->table('pg_database')->whereRaw("datname='$database'")->count() === 0) {
            $db_connection->commit();
            $db_connection->statement('CREATE DATABASE ' . '"' . $database . '"');
            sleep(30);
        }

        DB::connection('deploy_payment_pgsql')->statement('ALTER database "' . $database . '" OWNER TO common;');
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
