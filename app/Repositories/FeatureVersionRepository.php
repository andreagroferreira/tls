<?php

namespace App\Repositories;

use App\Models\FeatureVersion;
use App\Services\DbConnectionService;

class FeatureVersionRepository
{
    /**
     * @var FeatureVersion
     */
    protected $featureVersionModel;

    /**
     * @var DbConnectionService
     */
    private $dbConnectionService;

    /**
     * @param FeatureVersion      $featureVersionModel
     * @param DbConnectionService $dbConnectionService
     */
    public function __construct(FeatureVersion $featureVersionModel, DbConnectionService $dbConnectionService)
    {
        $this->featureVersionModel = $featureVersionModel;
        $this->dbConnectionService = $dbConnectionService;
        $this->setConnection($this->dbConnectionService->getConnection());
    }

    public function setConnection($connection)
    {
        $this->featureVersionModel->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->featureVersionModel->getConnectionName();
    }

    /**
     * @param array  $where
     * @param string $field
     *
     * @return object
     */
    public function fetch(array $where, string $field = '*'): object
    {
        return $this->featureVersionModel
            ->leftJoin('feature_version_configurations', 'feature_versions.fv_id', '=', 'feature_version_configurations.fvc_xref_fv_id')
            ->where($where)
            ->select([
                'fv_id',
                'fv_type',
                'fv_version',
                'fvc_id',
                'fvc_project',
                'fvc_country',
                'fvc_city',
            ])->get();
    }
}
