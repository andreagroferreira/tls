<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentEauditorLogJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private $params;

    /**
     * Create a new job instance.
     *
     * @param mixed $params
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
        } elseif ($this->params['queue_type'] ?? '' === 'profile_process_log') {
            $logService->sendEAuditorProfileLogs($this->params);
        } else {
            $logService->sendPaymentTransationLogs($this->params);
        }
    }
}
