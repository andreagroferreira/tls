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

        return $this->getFileLibraryInvoiceFileContent($transaction);
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
     * @return null|object
     */
    public function getFileLibraryInvoiceFileContent(Transactions $transaction): ?object
    {
        $file = getFilePath($transaction->toArray());

        $queryParams = 'path='.$file;

        try {
            $response = $this->apiService->callFileLibraryDownloadApi($queryParams);
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
     * @param string $lang
     *
     * @return array
     */
    public function getInvoiceContent(
        string $collection_name,
        string $issuer,
        string $service,
        string $lang
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
        ];
        $select_fields = '*.*';

        return $this->directusService->getContent(
            $collection_name,
            $select_fields,
            $select_filters,
            ['lang' => $lang, 'type' => $service]
        );
    }

    /**
     * @param int    $fg_id
     * @param string $client
     * @param array  $resolved_content
     * @param string $invoice_file_name
     *
     * @return void
     */
    public function sendInvoice(
        int $fg_id,
        string $client,
        array $resolved_content
    ): void {
        $form_group = $this->formGroupService->fetch($fg_id, $client);
        $form_user_email = $form_group['u_email'] ?? '';

        if (empty($form_user_email)) {
            return;
        }

        if (!empty($resolved_content['email_content']) && !empty($resolved_content['invoice_content'])) {
            $email_content = [
                'to' => $form_user_email,
                'subject' => $resolved_content['email_title'],
                'body' => $resolved_content['email_content'],
                'html2pdf' => [
                    $resolved_content['invoice_file_name'] => $resolved_content['invoice_content'],
                ],
            ];

            $this->apiService->callEmailApi('POST', 'send_email', $email_content);
        }
    }
}
