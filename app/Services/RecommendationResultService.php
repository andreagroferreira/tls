<?php

namespace App\Services;

use App\Repositories\RecommendationResultRepositories;

class RecommendationResultService
{
    protected $recommendationResultRepositories;
    protected $profileService;

    public function __construct(
        RecommendationResultRepositories $recommendationResultRepositories,
        DbConnectionService $dbConnectionService,
        ProfileService $profileService
    ) {
        $this->profileService = $profileService;
        $this->recommendationResultRepositories = $recommendationResultRepositories;
        $this->recommendationResultRepositories->setConnection($dbConnectionService->getConnection());
    }

    public function create($params)
    {
        $profile_data = $this->profileService->fetchProfile($params['rr_xref_f_id']);
        $params['rr_profile'] = $profile_data->profile ?? 'DEFAULT';

        return $this->recommendationResultRepositories->insert($params);
    }

    public function fetchByFId($id)
    {
        $select = [
            'rr_xref_f_id', 'rr_issuer', 'rr_sku', 'rr_result', 'rr_tech_creation',
            'rr_profile', 'rr_price', 'rr_currency', 'rr_service_script', 'rr_comment',
        ];

        return $this->recommendationResultRepositories->fetchByFId($id, $select);
    }

    public function delete($attributes)
    {
        if ($attributes['is_soft_delete'] == 'yes') {
            return $this->recommendationResultRepositories->softDelete($attributes['rr_id'], $attributes['rr_deleted_by']);
        }

        return $this->recommendationResultRepositories->delete($attributes['rr_id']);
    }
}
