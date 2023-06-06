<?php

namespace App\Services;

use App\Jobs\ReceiptJob;
use Illuminate\Support\Facades\Log;
use \Mpdf\Mpdf as PDF;

class ReceiptService
{
    protected $transactionService;
    protected $apiService;
    protected $directusService;
    protected $tokenResolveService;

    public function __construct(
        TransactionService $transactionService,
        ApiService $apiService,
        DirectusService $directusService,
        TokenResolveService $tokenResolveService
    ) {
        $this->transactionService = $transactionService;
        $this->apiService = $apiService;
        $this->directusService = $directusService;
        $this->tokenResolveService = $tokenResolveService;
    }

    /**
     * @param array $transaction
     *
     * @return null|array|string
     */
    public function getReceiptFileContent(array $transaction): ?array
    {
        if (blank($transaction)) {
            return null;
        }
        $select_filters = [
            'status' => [
                'eq' => 'published',
            ],
            'code' => [
                'in' => [substr($transaction['t_issuer'], 2, 3), substr($transaction['t_issuer'], 0, 2), 'ww'],
            ],
            'type' => [
                'eq' => $transaction['t_service'] . '_' . (($transaction['t_workflow'] == 'vac') ? 'onsite' : 'online'),
            ],
        ];

        $rawContent = $this->directusService->getContent(
            'tlspay_receipts',
            '*.*',
            $select_filters,
            ['lang' => 'en-us']
        );

        if (empty($rawContent)) {
            return null;
        }

        return $this->tokenResolveService->resolveReceiptTemplate($rawContent, $transaction, 'en-us');
    }

    /**
     * @param  string $transactionId
     * @param  string $fileName
     * 
     * @return null|array
     */
    public function generateReceipt(string $transactionId, string $fileName): ?array
    {
        $transaction = $this->transactionService->fetchTransaction([
            't_transaction_id' => $transactionId,
            't_status' => 'done',
            't_tech_deleted' => false]);

        if (blank($transaction)) {
            return [];
        }

        $checkIfFileExists = $this->apiService->callFileLibraryFilesApi('receipt?fileName=' . $fileName);

        if ($checkIfFileExists['status'] != 200) {
            Log::warning('Error getting the receipt file details from file-library for ' . json_encode($transaction['t_transaction_id']));
        }
        
        if (!empty($checkIfFileExists['body']['data'])) {
            return ['fileContent' => $this->downloadReceipt($transaction, array_first($checkIfFileExists['body']['data'])['path']), 'type' => 'download'];
        }

        dispatch(new ReceiptJob($transaction, $fileName))->onConnection('tlspay_receipt_queue')->onQueue('tlspay_receipt_queue');
        return ['type' => 'upload'];
    }
    
    /**
     * @param  array $transaction
     * @param  string $fileName
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return bool
     */
    public function saveReceipt(array $transaction, string $fileName): bool
    {
        $country = substr($transaction['t_issuer'], 0, 2);
        $city = substr($transaction['t_issuer'], 2, 3) . '/' . $transaction['t_xref_fg_id'];

        $content = $this->getReceiptFileContent($transaction);
        if (!$content['receipt_content']) {
            throw new \Exception('Error ' . $transaction['t_transaction_id'] . ' - Workflow:' . $transaction['t_workflow'] . ' - Type:' . $transaction['t_service']);
        }

        $pdf = new PDF(['autoScriptToLang' => true, 'autoArabic' => true, 'autoLangToFont' => true, 'packTableData' => true]);
        $pdf->WriteHTML($content['receipt_content']);
        $response = $this->apiService->callFileLibraryUploadApi(
            'country=' . $country . '&city=' . $city . '&fileName=' . $fileName . '&userName=tlspay',
            response()->make($pdf->OutputBinaryData(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]),
            'receipt'
        );
        unset($pdf);
        if ($response['status'] !== 200) {
            Log::warning('Transaction Error: receipt pdf upload failed');

            return false;
        }

        return true;
    }
    
    /**
     * @param  array $transaction
     * @param  string $filePath
     * 
     * @return null|object
     */
    private function downloadReceipt(array $transaction, string $filePath): ?object
    {
        try {
            $response = $this->apiService->callFileLibraryDownloadApi('path='.$filePath);
        } catch (\Exception $e) {
            Log::warning('Error downloading the receipt from file-library for '.json_encode($transaction['t_transaction_id']).' - "'.$e->getMessage().'"');

            return null;
        }

        if ($response['status'] != 200) {
            Log::warning('Error downloading the receipt from file-library for '.json_encode($transaction['t_transaction_id']));

            return null;
        }
        return $response['body'];
    }
}
