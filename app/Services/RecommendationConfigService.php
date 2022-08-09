<?php

namespace App\Services;

use App\Repositories\RecommendationConfigRepositories;
use Illuminate\Support\Facades\Cache;

class RecommendationConfigService
{
    protected $recommendationConfigRepositories;
    protected $cacheKey = "recommendation_rule_engine_payment_cache";

    public function __construct(
        RecommendationConfigRepositories $recommendationConfigRepositories,
        DbConnectionService $dbConnectionService
    ) {
        $this->recommendationConfigRepositories = $recommendationConfigRepositories;
        $this->recommendationConfigRepositories->setConnection($dbConnectionService->getConnection());
    }

    public function create($params) {
        $result = $this->recommendationConfigRepositories->insert($params);
        Cache::pull($this->cacheKey);
        return $result;
    }

    public function fetch($limit) {
        $select = ['rc_id', 'rc_file_name','rc_uploaded_by', 'rc_content', 'rc_file_size', 'rc_tech_creation'];
        return $this->recommendationConfigRepositories->fetch($select, $limit);
    }

    public function fetchByRcId($rc_id) {
        return $this->recommendationConfigRepositories->fetchByRcId($rc_id);
    }

}
