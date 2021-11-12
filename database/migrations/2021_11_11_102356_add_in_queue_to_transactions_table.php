<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddInQueueToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::beginTransaction();
        try{
            Schema::table('transactions', function (Blueprint $table) {
                $table->boolean('t_in_queue')->default(0);
            });
            DB::commit();
        } catch (\Exception $e){
            DB::rollBack();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::beginTransaction();
        try{
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('t_in_queue');
            });
            DB::commit();
        } catch (\Exception $e){
            DB::rollBack();
        }
    }
}
