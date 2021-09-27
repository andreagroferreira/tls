<?php


namespace App\Services;


use App\Repositories\RecommendationResultRepositories;

class RecommendationResultService
{
    protected $recommendationResultRepositories;

    public function __construct(
        RecommendationResultRepositories $recommendationResultRepositories,
        DbConnectionService $dbConnectionService
    ) {
        $this->recommendationResultRepositories = $recommendationResultRepositories;
        $this->recommendationResultRepositories->setConnection($dbConnectionService->getConnection());
    }

    public function create($params) {
        return $this->recommendationResultRepositories->insert($params);
    }

    public function fetchByFId($id) {
        $select = ['rr_xref_f_id', 'rr_sku', 'rr_id'];
        return $this->recommendationResultRepositories->fetchByFId($id, $select);
    }

    public function delete($attributes)
    {
        if ($attributes['is_soft_delete'] == 'yes') {
            return $this->recommendationResultRepositories->softDelete($attributes['rr_id'], $attributes['rr_deleted_by']);
        } else {
            return $this->recommendationResultRepositories->delete($attributes['rr_id']);
        }
    }

}
