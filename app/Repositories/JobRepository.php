<?php

namespace App\Repositories;

use App\Models\Job as JobModel;
use Illuminate\Support\Facades\DB;

class JobRepository
{
    protected $jobModel;

    public function __construct(JobModel $jobModel)
    {
        $this->jobModel = $jobModel;
    }

    public function setConnection($connectionName)
    {
        $this->jobModel->setConnection($connectionName);
    }

    public function countQueue()
    {
        return $this->jobModel->select('queue', DB::raw('count(1) as jobs'))->groupBy('queue')->get()->toArray();
    }

    public function fetchQueue($attributes)
    {
        $id = $attributes['id'] ?? null;
        $query = $this->jobModel->where('queue', $attributes['queue_name']);

        return $id ? $query->where('id', $id)->get() : $query->get();
    }
}
