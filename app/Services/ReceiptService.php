<?php

namespace App\Services;

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
}
