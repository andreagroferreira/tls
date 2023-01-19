<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InvoiceMailJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var array
     */
    protected $transaction;

    /**
     * @var string
     */
    protected $collection;

    /**
     * @param array  $transaction
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
        Log::info('InvoiceQueue - Generating invoice for transaction: '.json_encode($this->transaction['t_id']));

        try {
            $paymentService->sendInvoice($this->transaction, $this->collection);
            Log::info('InvoiceQueue - Generation finished successfully for transaction: '.json_encode($this->transaction['t_id']));
        } catch (\Exception $exception) {
            Log::error('InvoiceQueue error - '.$exception->getMessage());
            $error = [
                'error_code' => $exception->getCode(),
                'error_msg' => $exception->getMessage(),
                'error_stack' => $exception->getTraceAsString(),
            ];
            $paymentService->saveTransactionLog(
                $this->transaction['t_transaction_id'],
                $error,
                'invoice_queue_error'
            );
            $paymentService->PaymentTransationBeforeLog('invoice_queue', $error);
        }
    }
}
