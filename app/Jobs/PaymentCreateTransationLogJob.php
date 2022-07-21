<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class PaymentCreateTransationLogJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    private $t_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($t_id)
    {
        $this->t_id = $t_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $logService = app()->make('App\Services\PaymentService');
        $logService->sendPaymentCreateTransationLogs($this->t_id);
    }
}
