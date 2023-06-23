<?php

namespace App\Services;

use App\Models\Transactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    protected $invoiceDisk;
    protected $transactionService;
    protected $apiService;
    protected $directusService;
    protected $formGroupService;

    public function __construct(
        TransactionService $transactionService,
        ApiService $apiService,
        FormGroupService $formGroupService,
        DirectusService $directusService
    ) {
        $this->invoiceDisk = config('payment_gateway.invoice_disk');
        $this->transactionService = $transactionService;
        $this->apiService = $apiService;
        $this->formGroupService = $formGroupService;
        $this->directusService = $directusService;
    }

    /**
     * @param string $transaction_id
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     * @return null|object|string
     */
    public function getInvoiceFileContent(string $transaction_id)
    {
        /** @var Transactions $transaction */
        $transaction = $this->transactionService
            ->fetchByWhere([
                't_transaction_id' => $transaction_id,
                't_status' => 'done',
                't_tech_deleted' => false,
            ])
            ->first();

        if (blank($transaction)) {
            return null;
        }

        if ($transaction->t_invoice_storage === 's3') {
            return $this->getS3InvoiceFileContent($transaction);
        }

        if ($transaction->t_invoice_storage === 'minio') {
            return $this->getMinioInvoiceFileContent($transaction);
        }

        return $this->getFileLibraryInvoiceFileContent($transaction);
    }

    /**
     * generate.
     *
     * @param mixed $transaction
     *
     * @return bool
     */
    public function generate(array $transaction)
    {
        if (empty($transaction['t_callback_url'])) {
            Log::warning('Transaction Error: empty callback url');

            return false;
        }

        $gatewayReference = !empty($transaction['t_gateway_transaction_reference']) ? $transaction['t_gateway_transaction_reference'] : $transaction['t_gateway_transaction_id'];
        $data = [
            't_id' => $transaction['t_id'],
            'transaction_id' => $transaction['t_transaction_id'],
            'gateway_transaction_id' => $gatewayReference,
            'gateway' => $transaction['t_gateway'],
            'currency' => $transaction['t_currency'],
            'status' => $transaction['t_status'],
            'tech_creation' => $transaction['t_tech_creation'],
            'tech_modification' => $transaction['t_tech_modification'],
            'items' => $transaction['t_items'],
        ];

        try {
            $response = $this->apiService->callInvoiceApi($transaction['t_callback_url'], $data);
        } catch (\Exception $e) {
            Log::warning('Transaction Error: error callback url "'.$transaction['t_callback_url'].'"');

            return false;
        }

        if ($response['status'] != 200) {
            Log::warning('Transaction Error: generate receipt failed');

            return false;
        }

        return true;
    }

    /**
     * @param array $transaction
     *
     * @return bool
     */
    public static function generateInvoice(array $transaction): bool
    {
        return app(self::class)->generate($transaction);
    }

    /**
     * @param Transactions $transaction
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @return null|string
     */
    public function getS3InvoiceFileContent(Transactions $transaction): ?string
    {
        if (!array_has(config('filesystems.disks', []), $this->invoiceDisk)) {
            return null;
        }

        $storage = Storage::disk($this->invoiceDisk);
        $file = getFilePath($transaction->toArray(), 's3');

        if (!$storage->exists($file)) {
            return null;
        }

        return $storage->get($file);
    }

    /**
     * @param Transactions $transaction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     * @return string|null
     */
    public function getMinioInvoiceFileContent(Transactions $transaction): ?string
    {
        $path = getFilePath($transaction->toArray(), 'minio');

        try {
            $response = $this->apiService->callCustomerServiceInvoiceDownloadApi($path);
        } catch (\Exception $e) {
            Log::warning('Transaction Error: error customer-service api "'.$e->getMessage().'"');

            return null;
        }

        if ($response['status'] != 200) {
            Log::warning('Transaction Error: receipt download failed');

            return null;
        }

        return $response['body'];
    }

    /**
     * @param Transactions $transaction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     * @return null|object
     */
    public function getFileLibraryInvoiceFileContent(Transactions $transaction): ?object
    {
        $getFile = $this->apiService->callFileLibraryFilesApi('invoice?fileName='.$transaction->t_transaction_id);
        
        if($getFile['status'] != 200 || empty($getFile['body']['data'])) {
            Log::warning('Transaction Error: receipt download failed');

            return null;
        }
        
        $file  = array_first($getFile['body']['data']);
        
        try {
            $response = $this->apiService->callFileLibraryDownloadApi('path='.$file['path']);
        } catch (\Exception $e) {
            Log::warning('Transaction Error: error file-library api "'.$e->getMessage().'"');

            return null;
        }

        if ($response['status'] != 200) {
            Log::warning('Transaction Error: receipt download failed');

            return null;
        }

        return $response['body'];
    }

    /**
     * @param string $collection_name
     * @param string $issuer
     * @param string $service
     * @param string $language
     *
     * @return array
     */
    public function getInvoiceContent(
        string $collection_name,
        string $issuer,
        string $service,
        string $language
    ): array {
        $country = substr($issuer, 0, 2);
        $city = substr($issuer, 2, 3);

        $select_filters = [
            'status' => [
                'eq' => 'published',
            ],
            'code' => [
                'in' => [$city, $country, 'ww'],
            ],
            'type' => [
                'eq' => $service,
            ],
        ];
        $select_fields = '*.*';

        return $this->directusService->getContent(
            $collection_name,
            $select_fields,
            $select_filters,
            ['lang' => $language]
        );
    }

}
