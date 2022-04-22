<?php

namespace App\Listeners;

use App\Events\TransactionSyncEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\ApiService;
use App\Repositories\FailedJobRepository;
use App\Repositories\JobRepository;
use Illuminate\Support\Facades\Log;

class TransactionSyncListener
{
    protected $data;
    protected $client;
    protected $apiService;
    protected $jobRepository;
    protected $failedJobRepository;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        ApiService          $apiService,
        JobRepository       $jobRepository,
        FailedJobRepository $failedJobRepository
    )
    {
        $this->apiService = $apiService;
        $this->jobRepository = $jobRepository;
        $this->failedJobRepository = $failedJobRepository;
        /*$this->jobRepository->setConnection('payment_pgsql');
        $this->failedJobRepository->setConnection('payment_pgsql');*/
    }


    /**
     * Handle the event.
     *
     * @param \App\Events\TransactionSyncEvent $event
     * @return void
     */
    public function handle(TransactionSyncEvent $event)
    {
        $this->data = $event->data;
        $this->client = $event->client;
        $this->syncTransaction();
    }

    private function syncTransaction()
    {
        Log::info('listener syncTransaction:' . $this->client .'---'. json_encode($this->data));
        $response = $this->apiService->callTlsApi('POST', '/tls/v1/' . $this->client . '/sync_payment_action', $this->data);
        Log::info('listener syncTransaction $response:'. json_encode($response));
        if ($response['status'] != 200) {
            Log::info('syncTransaction:' . json_encode($this->data));
            Log::error('sync to tls fail');
        } else {
            Log::info('sync to tls success');
        }
    }
}
