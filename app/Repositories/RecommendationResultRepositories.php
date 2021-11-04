<?php


namespace App\Repositories;


use App\Models\RecommendationResult as RecommendationResultModel;

class RecommendationResultRepositories
{
    protected $recommendationResultModel;

    public function __construct(
        RecommendationResultModel $recommendationResultModel
    )
    {
        $this->recommendationResultModel = $recommendationResultModel;
    }

    public function setConnection($connection)
    {
        $this->recommendationResultModel->setConnection($connection);
    }

    public function insert($attributes)
    {
        return $this->recommendationResultModel->newInstance()->create($attributes);
    }

    public function fetchByFid($f_id, $select = ['*'], $with_delete = false)
    {
        return $this->recommendationResultModel
            ->select($select)
            ->where('rr_xref_f_id', $f_id)
            ->when(!$with_delete, function ($query) {
                return $query->where('rr_tech_deleted', false);
            })
            ->get();
    }

    public function delete($rr_id)
    {
        $this->recommendationResultModel->where('rr_id', $rr_id)->delete();
        return $this->recommendationResultModel->find($rr_id);
    }

    public function softDelete($rr_id, $deleted_by)
    {
        $this->recommendationResultModel->where('rr_id', $rr_id)->update([
            'rr_tech_deleted' => true,
            'rr_deleted_by'   => $deleted_by
        ]);
        return $this->recommendationResultModel->find($rr_id);
    }
}
