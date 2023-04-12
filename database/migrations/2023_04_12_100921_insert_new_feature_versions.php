<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertNewFeatureVersions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            DB::connection('deploy_payment_pgsql')->beginTransaction();
            $featureVersions = [1, 2];
            foreach ($featureVersions as $version) {
                $versionFreeTransaction = [
                    'fv_type' => 'free_transaction',
                    'fv_version' => $version,
                ];

                $this->createFeatureVersions($versionFreeTransaction);

                $versionAgentTransaction = [
                    'fv_type' => 'agent_transaction',
                    'fv_version' => $version,
                ];

                $this->createFeatureVersions($versionAgentTransaction);
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
        //
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
