<?php

namespace App\Repositories;

use App\Models\FailedJob as FailedJobModel;
use Illuminate\Support\Facades\DB;

class FailedJobRepository
{
    protected $failedJobModel;

    public function __construct(FailedJobModel $failedJobModel)
    {
        $this->failedJobModel = $failedJobModel;
    }

    public function setConnection($connectionName)
    {
        $this->failedJobModel->setConnection($connectionName);
    }

    public function countQueue()
    {
        return $this->failedJobModel->select('queue', DB::raw('count(1) as failed_jobs'))->groupBy('queue')->get()->toArray();
    }

    public function fetchQueue($attributes)
    {
        $id = $attributes['id'] ?? null;
        $query = $this->failedJobModel->where('queue', $attributes['queue_name']);

        return $id ? $query->where('id', $id)->get() : $query->get();
    }
}
