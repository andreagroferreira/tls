<?php

namespace App\Services;

use App\Repositories\RecommendationConfigRepositories;
use Illuminate\Support\Facades\Cache;

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

    public function create($params)
    {
        $result = $this->recommendationConfigRepositories->insert($params);
        $cache_key = getRecommendationRulesCacheKey();
        Cache::pull($cache_key);

        return $result;
    }

    public function fetch($limit)
    {
        $select = ['rc_id', 'rc_file_name', 'rc_uploaded_by', 'rc_content', 'rc_file_size', 'rc_tech_creation'];

        return $this->recommendationConfigRepositories->fetch($select, $limit);
    }

    public function fetchByRcId($rc_id)
    {
        return $this->recommendationConfigRepositories->fetchByRcId($rc_id);
    }
}
