<?php

namespace App\Repositories;

use App\Models\FailedJob as FailedJobModel;
use Illuminate\Support\Facades\Log;

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

    public function countQueue($queueName)
    {
        return $this->failedJobModel->where('queue', $queueName)->count();
    }

    public function fetchQueue($attributes)
    {
        $id = $attributes['id'] ?? null;
        $query = $this->failedJobModel->where('queue', $attributes['queue_name']);
        return $id ? $query->where('id', $id)->get() : $query->get();
    }
}
