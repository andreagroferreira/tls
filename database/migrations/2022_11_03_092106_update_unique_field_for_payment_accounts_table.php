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
        Schema::connection('deploy_payment_pgsql')->table('payment_accounts', function (Blueprint $table) {
            $exception = null;

            try {
                $this->dropIndex($table, ['pa_name', 'pa_type']);

            } catch (\Exception $e) {
                $exception = $e;
            }

            if ($exception !== null) {
                $this->dropUnique($table, ['pa_name', 'pa_type']);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('deploy_payment_pgsql')->table('payment_accounts', function (Blueprint $table) {
            $exception = null;

            try {
                $this->dropIndex($table, ['pa_name']);

            } catch (\Exception $e) {
                $exception = $e;
            }

            if ($exception !== null) {
                $this->dropUnique($table, ['pa_name']);
            }
        });
    }

    /**
     * @param $table
     * @param array $fields
     * @return void
     */
    private function dropUnique($table, array $fields): void
    {
        $table->dropUnique('payment_accounts_unique_name');
        $table->unique($fields, 'payment_accounts_unique_name');
    }

    /**
     * @param $table
     * @param array $fields
     * @return void
     */
    private function dropIndex($table, array $fields): void
    {
        $table->dropIndex('payment_accounts_unique_name');
        $table->unique($fields, 'payment_accounts_unique_name');
    }
}
