<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FawryPaymentJob implements ShouldQueue
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
        $fawryPaymentGateway = app()->make('App\PaymentGateway\FawryPaymentGateway');
        $fawryPaymentGateway->notify($this->params);
    }
}
