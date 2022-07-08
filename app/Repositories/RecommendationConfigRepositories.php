<?php


namespace App\Repositories;


use App\Models\RecommendationConfig as RecommendationConfigModel;

class RecommendationConfigRepositories
{
    protected $RecommendationConfigModel;

    public function __construct(
        RecommendationConfigModel $RecommendationConfigModel
    )
    {
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

}
