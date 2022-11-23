<?php

namespace App\Services;

use App\Repositories\RefundItemRepository;
use App\Repositories\RefundLogRepository;
use App\Repositories\RefundRepository;
use Illuminate\Support\Facades\DB;

class RefundService
{
    /**
     * @var RefundRepository
     */
    protected $refundRepository;

    /**
     * @var refundItemRepository
     *
     */
    protected $refundItemRepository;

    /**
     * @var RefundLogRepository
     */
    protected $refundLogRepository;

    /**
     * @var DbConnectionService
     */
    protected $dbConnectionService;

    /**
     * @var RefundItemsService
     */
    protected $refundItemsService;

    /**
     * @var TransactionItemsService
     */
    protected $transactionItemsService;

    /**
     * @param RefundRepository     $refundRepository
     * @param refundItemRepository $refundItemRepository
     * @param RefundLogRepository  $refundLogRepository
     * @param DbConnectionService  $dbConnectionService
     * @param RefundItemsService   $refundItemsService
     * @param TransactionItemsService $transactionItemsService
     */
    public function __construct(
        RefundRepository $refundRepository,
        RefundItemRepository $refundItemRepository,
        RefundLogRepository $refundLogRepository,
        DbConnectionService $dbConnectionService,
        RefundItemsService $refundItemsService,
        TransactionItemsService $transactionItemsService
    ) {
        $this->refundRepository = $refundRepository;
        $this->refundItemRepository = $refundItemRepository;
        $this->refundLogRepository = $refundLogRepository;
        $this->dbConnectionService = $dbConnectionService;
        $this->refundItemsService = $refundItemsService;
        $this->transactionItemsService = $transactionItemsService;
        $this->refundRepository->setConnection($this->dbConnectionService->getConnection());
        $this->refundItemRepository->setConnection($this->dbConnectionService->getConnection());
        $this->refundLogRepository->setConnection($this->dbConnectionService->getConnection());
    }

    /**
     * @param array $attributes
     *
     * @return array|false
     */
    public function create(array $attributes)
    {
        $db_connection = DB::connection($this->dbConnectionService->getConnection());
        $db_connection->beginTransaction();

        try {
            $refundRequest = $this->refundRepository->create($attributes);
            $this->refundItemRepository->createMany($refundRequest->r_id, $attributes['items']);

            $db_connection->commit();
        } catch (\Exception $e) {
            $db_connection->rollBack();

            return false;
        }

        return ['r_id' => $refundRequest->r_id];
    }

    /**
     * @param array $attributes
     *
     * @return array
     */
    public function getRefundTransactionItems(array $attributes): array
    {
        $transactionsRefundItems = $this->refundItemsService->getRefundTransactionItems($attributes);
        if (empty($transactionsRefundItems)) {
            return [];
        }

        return collect($transactionsRefundItems)->sortBy('t_id', SORT_DESC)->values()->toArray();
    }

    /**
     * @param  array $attributes
     * 
     * @return array
     */
    public function getRefundRequest(array $attributes): array
    {
        $where = collect([
                    ['refund_items.ri_xref_r_id', '=', $attributes['r_id']],
                ])->toArray();

        $refundData = $this->refundItemRepository->fetchRefundItems($where)->toArray();
        if (empty($refundData)) {
            return [];
        }

        $transactionId = array_get($refundData, '0.t_transaction_id');
        $transactionItems = $this->transactionItemsService->fetch(['ti_xref_transaction_id' => $transactionId])
            ->groupBy('ti_xref_f_id')
            ->toArray();
        if (empty($transactionItems)) {
            return [];
        }

        return $this->getRefundRequestItems($refundData, $transactionItems);
    }

    private function getRefundRequestItems($refundData, $transactionItems)
    {
        foreach ($refundData as $data) {
            if (isset($data['r_id']) && empty($refundRequest)) {
                $refundRequest = [
                    'r_id' => $data['r_id'],
                    'r_issuer' => $data['r_issuer'],
                    'r_reason_type' => $data['r_reason_type'],
                    'r_status' => $data['r_status'],
                    'r_appointment_date' => $data['r_appointment_date'],
                ];
            }
            if (empty($transaction) && empty($transaction)) {
                $transaction = [
                    't_id' => $data['t_id'],
                    'transaction_id' => $data['t_transaction_id'],
                    't_xref_fg_id' => $data['t_xref_fg_id'],
                    't_client' => $data['t_client'],
                    't_issuer' => $data['t_issuer'],
                    'gateway' => $data['t_gateway'],
                    'agent_gateway' => $data['t_payment_method'],
                    'gateway_transaction_id' => $data['t_gateway_transaction_id'],
                    'currency' => $data['t_currency'],
                    'status' => $data['t_status'],
                    'service' => $data['t_service'],
                    'tech_creation' => $data['t_tech_creation'],
                    'tech_modification' => $data['t_tech_modification']
                ];
            }
            $refundItems[$data['ti_id']] = [
                'ri_id' => $data['ri_id'],
                'ri_quantity' => $data['ri_quantity'],
                'ri_amount' => floatval($data['ri_amount']),
                'ri_reason_type' => $data['ri_reason_type'],
                'ri_status' => $data['ri_status'],
                'ri_invoice_path' => $data['ri_invoice_path'],
                'rl_agent' => $data['rl_agent'],
            ];
        }
        foreach ($transactionItems as $formId => $services) {
            $items['f_id'] = $formId;
            $items['skus'] = [];
            foreach ($services as $service) {
                $items['skus'][] = [
                    'ti_id' => $service['ti_id'],
                    'sku' => $service['ti_fee_type'],
                    'price' => $service['ti_amount'],
                    'vat' => $service['ti_vat'],
                    'quantity' => $service['ti_quantity'],
                    'amount_gross' => ($service['ti_vat'] / 100 * $service['ti_amount']) + $service['ti_amount'],
                    'refund_items' => $refundItems[$service['ti_id']] ?? [],
                ];
            }
            $transaction['items'][] = $items;
        }
        $refundRequest['transaction'] = $transaction;

        return $refundRequest;
    }
}
