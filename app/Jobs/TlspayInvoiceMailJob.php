<?
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
  
class TlspayInvoiceMailJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $emailBody;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($emailBody)
    {
        $this->emailBody = $emailBody;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $emailService = app()->make('App\Services\QueueService');
        $emailService->sendMail($this->emailBody);
    }
}

?>