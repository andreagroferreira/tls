<?php

namespace App\Jobs;

//use App\Events\TransactionSyncEvent;
//use Illuminate\Support\Facades\Log;
//use App\Services\PaymentService;

class TransactionSyncJob extends Job
{
    private $data;
    private $client;

    /**
     * Create a new job instance.
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
        //Log::info('Trancaction event');
        //event(new TransactionSyncEvent($this->client, $this->data));
        //$this->fail();
        
        $PaymentService = app()->make('App\Services\PaymentService');
        $PaymentService->syncTransaction($this->client, $this->data);



    }
}
