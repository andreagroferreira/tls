<?php

namespace App\Services;

use App\Repositories\RecommendationConfigRepositories;

class RecommendationConfigService
{
    protected $recommendationConfigRepositories;

    public function __construct(
        RecommendationConfigRepositories $recommendationConfigRepositories,
        DbConnectionService $dbConnectionService
    ) {
        $this->recommendationConfigRepositories = $recommendationConfigRepositories;
        $this->recommendationConfigRepositories->setConnection($dbConnectionService->getConnection());
    }

    public function create($params) {
        return $this->recommendationConfigRepositories->insert($params);
    }

}
