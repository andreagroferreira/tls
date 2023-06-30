<?php

namespace App\Jobs;

class TransactionSyncJob extends Job
{
    private $data;
    private $client;

    /**
     * Create a new job instance.
     *
     * @param mixed $client
     *
     * @return void
     */
    public function __construct($client, array $data)
    {
        $this->data = $data;
        $this->client = $client;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $PaymentService = app()->make('App\Services\QueueService');
        $PaymentService->syncTransaction($this->client, $this->data);
    }
}
