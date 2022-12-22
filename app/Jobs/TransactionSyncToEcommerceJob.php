<?php

namespace App\Jobs;

class TransactionSyncToEcommerceJob extends Job
{
    /**
     * @var int
     */
    private $fgId;

    /**
     * @var array
     */
    private $data;

    /**
     * @param int   $fgId
     * @param array $data
     */
    public function __construct(int $fgId, array $data)
    {
        $this->fgId = $fgId;
        $this->data = $data;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $queueService = app()->make('App\Services\QueueService');
        $queueService->syncTransactionToEcommerce($this->fgId, $this->data);
    }
}
