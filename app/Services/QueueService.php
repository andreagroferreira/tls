<?php

namespace App\Services;

use App\Repositories\JobRepository;
use App\Repositories\FailedJobRepository;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Repositories\ActionRepository;

class QueueService
{
    protected $dbConnectionService;
    protected $JobRepository;
    protected $failedJobRepository;
    protected $apiService;
    private $actionRepository;

    public function __construct(
        DbConnectionService $dbConnectionService,
        JobRepository       $JobRepository,
        FailedJobRepository $failedJobRepository,
        ApiService $apiService,
        ActionRepository $actionRepository
    )
    {
        $this->dbConnectionService = $dbConnectionService;
        $this->JobRepository = $JobRepository;
        $this->JobRepository->setConnection($this->dbConnectionService->getConnection());
        $this->failedJobRepository = $failedJobRepository;
        $this->failedJobRepository->setConnection($this->dbConnectionService->getConnection());
        $this->apiService  = $apiService;
        $this->actionRepository = $actionRepository;
    }


    public function resend($params)
    {
        $queue_name= $params['queue_name'];
        Artisan::call('queue:retry --queue='.$queue_name);

        return [
            'status' => 'success',
            'message' => 'Transaction has been resend'
        ];
    }

    public function health()
    {
        $jobs = $this->JobRepository->countQueue();
        $failed_jobs = $this->failedJobRepository->countQueue();
        $jobs_arr = [];
        foreach ($jobs as $k => $v) {
            $jobs_arr[$v['queue']]['jobs'] = $v['jobs'];
        }
        $failed_jobs_arr = [];
        foreach ($failed_jobs as $k => $v) {
            $failed_jobs_arr[$v['queue']] = $v;
        }
        foreach ($failed_jobs_arr as $k => $v) {
            $jobs_arr[$k]['failed_jobs'] = $v['failed_jobs'];
        }
        return $jobs_arr;
    }

    public function syncTransaction($client,$data){
        Log::info('QueueService syncTransaction:' . $client .'---'. json_encode($data));
        $response = $this->apiService->callTlsApi('POST', '/tls/v1/' . $client . '/sync_payment_action', $data);
        Log::info('QueueService syncTransaction $response:'. json_encode($response));
        if ($response['status'] != 200) {
            Log::error('QueueService sync to tls fail');
            throw new \Exception("sync to tls fail");
        } else {
            foreach ($data['t_items'] as $item) {
                $this->actionRepository->clearActionCache($item['f_id']);
            }
            Log::info('QueueService sync to tls success');
        }
    }

}
