<?php


namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    protected $invoiceDisk;
    protected $transactionService;
    protected $apiService;


    public function __construct(
        TransactionService $transactionService,
        ApiService $apiService
    )
    {
        $this->invoiceDisk = config('payment_gateway.invoice_disk');
        $this->transactionService = $transactionService;
        $this->apiService = $apiService;
    }

    public function generate($transaction)
    {
        if (empty($transaction['t_callback_url'])) {
            Log::warning('Transaction Error: empty callback url');
            return false;
        }

        $callback_url = $transaction['t_callback_url'];
        $data         = [
            't_id'                   => $transaction['t_id'],
            'transaction_id'         => $transaction['t_transaction_id'],
            'gateway_transaction_id' => $transaction['t_gateway_transaction_id'],
            'gateway'                => $transaction['t_gateway'],
            'currency'               => $transaction['t_currency'],
            'status'                 => $transaction['t_status'],
            'tech_creation'          => $transaction['t_tech_creation'],
            'tech_modification'      => $transaction['t_tech_modification'],
            'items'                  => $transaction['t_items']
        ];

        try{
            $response = $this->apiService->callInvoiceApi($callback_url, $data);
        } catch (\Exception $e) {
            Log::warning('Transaction Error: error callback url "' . $transaction['t_callback_url'] . '"');
            return false;
        }

        if ($response['status'] != 200) {
            Log::warning('Transaction Error: generate receipt failed');
            return false;
        }
        return true;
    }


    public function getInvoiceFileContent($transaction_id)
    {
        if (!array_has(config('filesystems.disks', []), $this->invoiceDisk)) {
            return false;
        }

        $transaction = $this->transactionService
            ->fetchByWhere(['t_transaction_id' => $transaction_id, 't_status' => 'done', 't_tech_deleted' => false])
            ->first();

        if (blank($transaction)) {
            return false;
        }

        $storage = Storage::disk($this->invoiceDisk);
        $file = $this->getFilePath($transaction->toArray());

        if (!$storage->exists($file)) {
            return false;
        }

        return $storage->get($file);
    }

    protected function getFilePath(array $transaction)
    {
        return array_get($transaction, 't_client') . '/' . array_get($transaction, 't_xref_fg_id') . '/' . array_get($transaction, 't_transaction_id') . '.pdf';
    }
}
