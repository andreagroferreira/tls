<?php

namespace App\Traits;

use App\Models\FeatureVersion;
use App\Repositories\FeatureVersionRepository;
use App\Services\DbConnectionService;

trait FeatureVersionsTrait
{
    /**
     * @param string $issuer
     * @param string $fvType
     *
     * @return null|object
     */
    public function getFeatureVersion(string $issuer, string $fvType): ?object
    {
        $country = substr($issuer, 0, 2);
        $city = substr($issuer, 2, 3);
        $client = substr($issuer, 6, 2);

        $featureVersionRepository = new FeatureVersionRepository(new FeatureVersion(), new DbConnectionService());
        $where = [
            'fvc_project' => getProjectId($client),
            'fvc_country' => $country,
            'fvc_city' => $city,
            'fv_type' => $fvType,
        ];
        $featureVersionsCityData = $featureVersionRepository->fetch($where);
        if ($featureVersionsCityData->toArray()) {
            return $featureVersionsCityData;
        }

        $where['fvc_city'] = 'All';
        $featureVersionsCountryData = $featureVersionRepository->fetch($where);
        if ($featureVersionsCountryData->toArray()) {
            return $featureVersionsCountryData;
        }

        $where['fvc_country'] = 'All';

        return $featureVersionRepository->fetch($where);
    }

    /**
     * @param int    $version
     * @param string $issuer
     * @param string $fvType
     *
     * @return bool
     */
    public function isVersion(int $version, string $issuer, string $fvType): bool
    {
        $versionData = $this->getFeatureVersion($issuer, $fvType)->toArray();
        if (empty($versionData)) {
            return false;
        }

        return array_first($versionData)['fv_version'] === $version;
    }
}
