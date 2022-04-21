<?php

namespace App\Events;

use Illuminate\Support\Facades\Log;

class TransactionSyncEvent extends Event
{
    public $data;
    public $client;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($client, $data)
    {
        $this->data = $data;
        $this->client = $client;
    }
}
