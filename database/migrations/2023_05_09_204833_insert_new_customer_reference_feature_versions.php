<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertNewCustomerReferenceFeatureVersions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('deploy_payment_pgsql')->beginTransaction();
        $featureVersions = [1, 2];
        foreach ($featureVersions as $version) {
            $this->createFeatureVersions([
                'fv_type' => 'aj_customer_reference',
                'fv_version' => $version,
            ]);
        }
        DB::connection('deploy_payment_pgsql')->commit();
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
