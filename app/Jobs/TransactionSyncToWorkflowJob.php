<?php

namespace App\Jobs;

class TransactionSyncToWorkflowJob extends Job
{
    /**
     * @var string
     */
    private $client;

    /**
     * @var string
     */
    private $location;

    /**
     * @var array
     */
    private $data;

    /**
     * @param string $client
     * @param string $location
     * @param array  $data
     */
    public function __construct(string $client, string $location, array $data)
    {
        $this->client = $client;
        $this->location = $location;
        $this->data = $data;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $queueService = app()->make('App\Services\QueueService');
        $queueService->syncTransactionToWorkflow($this->client, $this->location, $this->data);
    }
}
