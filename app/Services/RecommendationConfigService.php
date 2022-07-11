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

    public function fetch($limit) {
        $select = ['rc_id', 'rc_file_name', 'rc_content', 'rc_file_size', 'rc_tech_creation'];
        return $this->recommendationConfigRepositories->fetch($select,$limit);
    }

    public function fetchByRcId($rc_id) {
        return $this->recommendationConfigRepositories->fetchByRcId($rc_id);
    }

}
