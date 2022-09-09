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
     * Array with the details of the payment transaction
     *
     * @var array
     */
    protected $transaction;

    /**
     * Array with the invoice content to generate PDF and store in Filelibrary
     *
     * @var array
     */
    protected $invoiceContent;

    /**
     * Array with the details required to send email (i.e., email address, subject line, body, html content for PDF to be used as attachment)
     *
     * @var array
     */
    protected $emailContent;

    /**
     * Create a new job instance.
     * 
     * @param array $transaction
     * @param array $invoiceContent
     * @param array $emailContent
     * @return void
     */
    public function __construct($transaction, $invoiceContent, $emailContent)
    {
        $this->transaction = $transaction;
        $this->invoiceContent = $invoiceContent;
        $this->emailContent = $emailContent;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $service = app()->make('App\Services\QueueService');
        $response = $service->storePDF($this->transaction, $this->invoiceContent);
        $service->sendMail($this->emailContent);
    }
}