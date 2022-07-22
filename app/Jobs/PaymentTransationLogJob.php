<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class PaymentTransationLogJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    private $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $logService = app()->make('App\Services\PaymentService');
        if ($this->params['queue_type'] ?? '' === 'create_payment_order') {
            $logService->sendCreatePaymentOrderLogs($this->params);
        } else {
            $logService->sendPaymentTransationLogs($this->params);
        }
    }
}
