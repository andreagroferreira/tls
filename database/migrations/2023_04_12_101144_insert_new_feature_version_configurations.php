<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertNewFeatureVersionConfigurations extends Migration
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
            $client = $this->getProjectId();
            $types = [5 => 'free_transaction', 6 => 'agent_transaction'];
            foreach ($types as $key => $type) {
                $projectFeatureVersionConfigData = [
                    'fvc_project' => $client,
                    'fvc_country' => 'All',
                    'fvc_city' => 'All',
                    'fvc_xref_fv_id' => $key,
                ];

                $this->createFeatureVersionConfigurations($projectFeatureVersionConfigData);
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
     * @return false|string
     */
    private function getProjectId()
    {
        $project = getenv('CLIENT');

        switch ($project) {
            case 'gss-us':
                $projectCode = 'us';

                break;

            case 'srf-fr':
                $projectCode = 'srf_fr';

                break;

            case 'hmpo-uk':
                $projectCode = 'hmpo_uk';

                break;

            case 'leg-be':
                $projectCode = 'leg_be';

                break;

            case 'leg-de':
                $projectCode = 'leg_de';

                break;

            case 'biolab-ma':
                $projectCode = 'biolab_ma';

                break;

            default:
                $projectCode = substr($project, -2);
        }

        return $projectCode;
    }

    /**
     * @param array $projectFeatureVersionConfigData
     *
     * @return void
     */
    private function createFeatureVersionConfigurations(array $projectFeatureVersionConfigData): void
    {
        DB::connection('deploy_payment_pgsql')->table('feature_version_configurations')->insert($projectFeatureVersionConfigData);
    }
}
