<?php


namespace App\Repositories;


use App\Models\Job as JobModel;

class JobRepository
{
    protected $jobModel;

    public function __construct(JobModel $jobModel)
    {
        $this->jobModel = $jobModel;
    }

    public function setConnection($connectionName){
        $this->jobModel->setConnection($connectionName);
    }

    public function countQueue($queueName) {
        return $this->jobModel->where('queue', $queueName)->get()->count();
    }

    public function fetchQueue($attributes)
    {
        $id = $attributes['id'] ?? null;
        $query = $this->jobModel->where('queue', $attributes['queue_name']);
        return $id ? $query->where('id', $id)->get() : $query->get();
    }
}
