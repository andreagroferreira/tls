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
            Schema::connection('deploy_payment_pgsql')->table('payment_accounts', function (Blueprint $table) {
                $table->dropIndex('payment_accounts_unique_name');
                $table->unique(['pa_name', 'pa_type'], 'payment_accounts_unique_name');
            });
        } catch (\Exception $exception) {
            $message = 'index "payment_accounts_payment_accounts_unique_name_index" does not exist';
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
        try {
            Schema::connection('deploy_payment_pgsql')->table('payment_accounts', function (Blueprint $table) {
                $table->dropIndex('payment_accounts_unique_name');
                $table->unique(['pa_name'], 'payment_accounts_unique_name');
            });
        } catch (\Exception $exception) {
            $message = 'index "payment_accounts_payment_accounts_unique_name_index" does not exist';
            if ($exception->getCode() === '42704' && str_contains($exception->getMessage(), $message)) {
                return;
            }
            throw $exception;
        }
    }
}
