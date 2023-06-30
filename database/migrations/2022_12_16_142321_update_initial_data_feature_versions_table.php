<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateInitialDataFeatureVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            DB::connection('deploy_payment_pgsql')->statement('TRUNCATE TABLE feature_versions RESTART IDENTITY CASCADE');
            DB::connection('deploy_payment_pgsql')->beginTransaction();
            $featureVersions = [1, 2];
            foreach ($featureVersions as $version) {
                $versionInvoice = [
                    'fv_type' => 'invoice',
                    'fv_version' => $version,
                ];

                $this->createFeatureVersions($versionInvoice);

                $versionTransactionSync = [
                    'fv_type' => 'transaction_sync',
                    'fv_version' => $version,
                ];

                $this->createFeatureVersions($versionTransactionSync);
            }

            DB::connection('deploy_payment_pgsql')->commit();
        } catch (\Exception $e) {
            DB::connection('deploy_payment_pgsql')->rollBack();

            throw new LogicException($e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }

    /**
     * @param array $versionData
     *
     * @return void
     */
    private function createFeatureVersions(array $versionData): void
    {
        DB::connection('deploy_payment_pgsql')->table('feature_versions')->insert($versionData);
    }
}
