<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DbConnectionService
{
    protected $connection = 'payment_pgsql';

    public function getConnection()
    {
        return $this->connection;
    }

    public function setConnection($connection)
    {
        if (!Arr::exists(config('database.connections'), $connection)) {
            return false;
        }

        $this->connection = $connection;

        return true;
    }

    public function getDbNowTime()
    {
        return DB::connection($this->connection)
            ->table('pg_database')
            ->selectRaw('now()')
            ->first()
            ->now;
    }
}
