<?php

namespace App\Services;

use App\Models\Transactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReceiptService
{
    protected $transactionService;
    protected $apiService;
    protected $directusService;
    protected $formGroupService;
    protected $tokenResolveService;

    public function __construct(
        TransactionService $transactionService,
        ApiService $apiService,
        FormGroupService $formGroupService,
        DirectusService $directusService,
        TokenResolveService $tokenResolveService
    ) {
        $this->transactionService = $transactionService;
        $this->apiService = $apiService;
        $this->formGroupService = $formGroupService;
        $this->directusService = $directusService;
        $this->tokenResolveService = $tokenResolveService;
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
    public function getReceiptFileContent(string $transaction_id)
    {
        $transaction = $this->transactionService->fetchTransaction([
            't_transaction_id' => $transaction_id,
            't_status' => 'done',
            't_tech_deleted' => false]);
        if (blank($transaction)) {
            return null;
        }

        $rawContent = $this->getReceiptContent($transaction);
        if(empty($rawContent)) {
            return null;
        }

        return  $this->tokenResolveService->resolveReceiptTemplate($rawContent, $transaction, 'en-us');
    }



    /**
     * @param string $collection_name
     * @param string $issuer
     * @param string $service
     * @param string $lang
     *
     * @return array
     */
    public function getReceiptContent(array $transaction): array
    {
        $country = substr($transaction['t_issuer'], 0, 2);
        $city = substr($transaction['t_issuer'], 2, 3);
        $select_filters = [
            'status' => [
                'eq' => 'published',
            ],
            'code' => [
                'in' => [$city, $country, 'ww'],
            ],
            'type' => [
                'eq' => $transaction['t_service'].'_'.(($transaction['t_workflow'] == 'vac') ? 'onsite' : 'online'),
            ],
        ];
        $select_fields = '*.*';

        return $this->directusService->getContent(
            'tlspay_receipts',
            $select_fields,
            $select_filters,
            ['lang' => 'en-us']
        );
    }

}
