<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUniqueFieldForPaymentAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            $this->dropUnique(['pa_name', 'pa_type']);
        } catch (\Exception $exception) {
            if ($exception->getCode() === '42704') {
                $this->dropIndex(['pa_name', 'pa_type']);
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
        try {
            $this->dropUnique(['pa_name']);
        } catch (\Exception $exception) {
            if ($exception->getCode() === '42704') {
                $this->dropIndex(['pa_name']);
                return;
            }
            throw $exception;
        }
    }

    /**
     * @param array $fields
     * @return void
     */
    private function dropUnique(array $fields): void
    {
        Schema::connection('deploy_payment_pgsql')->table('payment_accounts', function (Blueprint $table) use ($fields) {
            $table->dropUnique('payment_accounts_unique_name');
            $table->unique($fields, 'payment_accounts_unique_name');
        });
    }

    /**
     * @param array $fields
     * @return void
     */
    private function dropIndex(array $fields): void
    {
        Schema::connection('deploy_payment_pgsql')->table('payment_accounts', function (Blueprint $table) use ($fields) {
            $table->dropIndex('payment_accounts_unique_name');
            $table->unique($fields, 'payment_accounts_unique_name');
        });
    }
}
