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
        $exception = null;

        try {
            $this->dropUnique(['pa_name', 'pa_type']);
        } catch (\Exception $e) {
            $exception = $e;
        }

        if ($exception !== null) {
            $this->dropIndex(['pa_name', 'pa_type']);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $exception = null;

        try {
            $this->dropUnique(['pa_name']);
        } catch (\Exception $e) {
            $exception = $e;
        }

        if ($exception !== null) {
            $this->dropIndex(['pa_name']);
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
