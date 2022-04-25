<?php

namespace App\Services;

use App\Repositories\JobRepository;
use App\Repositories\FailedJobRepository;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class QueueService
{
    protected $dbConnectionService;
    protected $JobRepository;
    protected $failedJobRepository;
    protected $apiService;

    public function __construct(
        DbConnectionService $dbConnectionService,
        JobRepository       $JobRepository,
        FailedJobRepository $failedJobRepository,
        ApiService $apiService
    )
    {
        $this->dbConnectionService = $dbConnectionService;
        $this->JobRepository = $JobRepository;
        $this->JobRepository->setConnection($this->dbConnectionService->getConnection());
        $this->failedJobRepository = $failedJobRepository;
        $this->failedJobRepository->setConnection($this->dbConnectionService->getConnection());
        $this->apiService  = $apiService;
    }


    public function resend($params)
    {
        $failedJobs = $this->failedJobRepository->fetchQueue($params)->toArray();
        if (empty($failedJobs)) {
            return [
                'status' => 'fail',
                'error' => 'job_not_found',
                'message' => 'Failed transaction job not found'
            ];
        }
        foreach ($failedJobs as $failedJob) {
            Artisan::call('queue:retry', ['id' => $failedJob['id']]);
        }
        return [
            'status' => 'success',
            'message' => 'Transaction has been resend'
        ];
    }

    public function health($params)
    {
        $jobs = $this->JobRepository->countQueue($params['queue_name']);
        $failed_jobs = $this->failedJobRepository->countQueue($params['queue_name']);
        return [
            'jobs' => $jobs,
            'failed_jobs' => $failed_jobs
        ];
    }

    public function syncTransaction($client,$data){
        Log::info('QueueService syncTransaction:' . $client .'---'. json_encode($data));
        $response = $this->apiService->callTlsApi('POST', '/tls/v1/' . $client . '/sync_payment_action', $data);
        Log::info('QueueService syncTransaction $response:'. json_encode($response));
        if ($response['status'] != 200) {
            Log::error('QueueService sync to tls fail');
            throw new \Exception("sync to tls fail");
        } else {
            Log::info('QueueService sync to tls success');
        }
    }

}
