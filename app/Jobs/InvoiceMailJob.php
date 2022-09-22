<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
  
class InvoiceMailJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var array
     */
    protected $transaction;

    /**
     * @var string
     */
    protected $collection;

    /**
     * @param array $transaction
     * @param string $collection
     */
    public function __construct(array $transaction, string $collection)
    {
        $this->transaction = $transaction;
        $this->collection = $collection;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $paymentService = app()->make('App\Services\PaymentService');
        $paymentService->sendInvoice($this->transaction, $this->collection);
    }
}