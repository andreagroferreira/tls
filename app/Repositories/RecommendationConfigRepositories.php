<?php

namespace App\Repositories;

use App\Models\RecommendationConfig as RecommendationConfigModel;

class RecommendationConfigRepositories
{
    protected $RecommendationConfigModel;

    public function __construct(
        RecommendationConfigModel $RecommendationConfigModel
    ) {
        $this->RecommendationConfigModel = $RecommendationConfigModel;
    }

    public function setConnection($connection)
    {
        $this->RecommendationConfigModel->setConnection($connection);
    }

    public function insert($attributes)
    {
        return $this->RecommendationConfigModel->newInstance()->create($attributes);
    }

    public function fetch($select = ['*'], $limit, $with_delete = false)
    {
        return $this->RecommendationConfigModel
            ->select($select)
            ->orderByDesc('rc_tech_creation')
            ->limit($limit)
            ->when(!$with_delete, function ($query) {
                return $query->where('rc_tech_deleted', false);
            })
            ->get();
    }

    public function fetchByRcId($rc_id)
    {
        return $this->RecommendationConfigModel->find($rc_id);
    }
}
