<?php

namespace App\Console\Commands;

use App\Repositories\TransactionRepository;
use App\Services\PaymentInitiateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Class GetPayuReference.
 *
 * @category Console_Command
 */
class GetPayuReference extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'get_payu_reference';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get PayU Transaction Reference';

    /**
     * @var string
     */
    private $appId;

    /**
     * @var string
     */
    private $privateKey;

    /**
     * Execute the console command.
     *
     * @param TransactionRepository  $transactionRepository
     * @param PaymentInitiateService $paymentInitiateService
     *
     * @throws \Exception
     *
     * @return void
     */
    public function handle(TransactionRepository $transactionRepository, PaymentInitiateService $paymentInitiateService): void
    {
        foreach ($this->getPaymentIds() as $paymentId) {
            $result = $paymentInitiateService->paymentInitiate(
                'get',
                'https://api.paymentsos.com/payments/'.$paymentId['id'],
                '',
                false,
                $this->getHeader()
            );
            $result = json_decode($result, true);

            $charges = $result['related_resources']['charges'] ?? [];
            foreach ($charges as $charge) {
                $chargeData = $paymentInitiateService->paymentInitiate(
                    'get',
                    $charge['href'],
                    '',
                    false,
                    $this->getHeader()
                );
                $chargeData = json_decode($chargeData, true);
                $chargeId = $chargeData['id'];
                $referenceCode = $chargeData['provider_data']['transaction_id'];

                $transaction = $transactionRepository->fetch(['t_gateway_transaction_id' => $chargeId]);

                if ($transaction !== null) {
                    $transactionRepository->update(['t_gateway_transaction_id' => $chargeId], ['t_gateway_transaction_reference' => $referenceCode]);
                }
            }
        }
    }

    /**
     * @return array
     */
    private function getHeader(): array
    {
        $sandbox = $this->isSandBox();

        return [
            'app_id: '.$this->appId,
            'private_key: '.$this->privateKey,
            'api-version: 1.3.0',
            'x-payments-os-env: '.$sandbox ? 'test' : 'live',
            'Content-Type: application/json',
        ];
    }

    /**
     * @return bool
     */
    private function isSandBox(): bool
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    private function getPaymentIds(): array
    {
        $transactionIds = json_decode(File::get(base_path('transaction_ids.json')), true);
        $this->setPayuCredentials(array_key_first($transactionIds));

        return current($transactionIds);
    }

    /**
     * @param string $issuer
     *
     * @throws \Exception
     *
     * @return void
     */
    private function setPayuCredentials(string $issuer): void
    {
        $isSandbox = $this->isSandBox();

        switch ($issuer) {
            case 'ke2de':
                $this->appId = getenv($isSandbox ? 'PAYU_KENYA_SANDBOX_APP_ID' : 'PAYU_KEALL2DE_APP_ID');
                $this->privateKey = getenv($isSandbox ? 'PAYU_KENYA_SANDBOX_PRIVATE_KEY' : 'PAYU_KEALL2DE_PRIVATE_KEY');

                break;

            case 'ke2be':
                $this->appId = getenv($isSandbox ? 'PAYU_KENYA_SANDBOX_APP_ID' : 'PAYU_KEALL2BE_APP_ID');
                $this->privateKey = getenv($isSandbox ? 'PAYU_KENYA_SANDBOX_PRIVATE_KEY' : 'PAYU_KEALL2BE_PRIVATE_KEY');

                break;

            case 'ng2be':
                $this->appId = getenv($isSandbox ? 'PAYU_NGALL2BE_SANDBOX_APP_ID' : 'PAYU_NGALL2BE_APP_ID');
                $this->privateKey = getenv($isSandbox ? 'PAYU_NGALL2BE_SANDBOX_PRIVATE_KEY' : 'PAYU_NGALL2BE_PRIVATE_KEY');

                break;

            default:
                throw new \Exception('Error fetching environment variables');
        }
    }
}
