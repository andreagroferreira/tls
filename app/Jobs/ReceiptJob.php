<?php

namespace App\Jobs;
use Illuminate\Support\Facades\Log;

class ReceiptJob extends Job
{
    /**
     * @var array
     */
    private $transaction;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @param array  $transaction
     * @param string $fileName
     */
    public function __construct(array $transaction, string $fileName)
    {
        $this->transaction = $transaction;
        $this->fileName = $fileName;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $receiptService = app()->make('App\Services\ReceiptService');
        Log::info('ReceiptQueue - Generating receipt for transaction: ' . json_encode($this->transaction['t_transaction_id']));

        try {
            $receiptService->saveReceipt($this->transaction, $this->fileName);
            Log::info('ReceiptQueue - Receipt generation finished successfully for transaction: ' . json_encode($this->transaction['t_transaction_id']));
        } catch (\Exception $exception) {
            Log::error('ReceiptQueue error - ' . [
                'error_code' => $exception->getCode(),
                'error_msg' => $exception->getMessage(),
                'error_stack' => $exception->getTraceAsString(),
            ]);
        }
    }
}
