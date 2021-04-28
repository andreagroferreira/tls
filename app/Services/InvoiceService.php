<?php


namespace App\Services;

use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    protected $invoiceDisk;
    protected $transactionService;


    public function __construct(TransactionService $transactionService)
    {
        $this->invoiceDisk = config('payment_gateway.invoice_disk');
        $this->transactionService = $transactionService;
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
