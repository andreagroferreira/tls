<?php

namespace App\Console\Commands;

use App\Models\TransferTableTransaction;
use App\Repositories\TransactionItemsRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\TransferTableTransactionRepository;
use App\Services\DbConnectionService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class GetTransactionsToMigrate.
 *
 * @category Console_Command
 */
class GetTransactionsToMigrate extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'get_transactions_to_migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get TLSconnect basket and migrate to e-commerce basket and payment-api transactions';
    protected $transactionRepository;
    protected $transactionItemsRepository;
    protected $dbConnectionService;

    /**
     * Execute the console command.
     *
     * @param TransferTableTransactionRepository $transferTableTransactionRepository
     * @param TransactionRepository              $transactionRepository
     * @param TransactionItemsRepository         $transactionItemsRepository
     * @param DbConnectionService                $dbConnectionService
     *
     * @throws Exception
     *
     * @return void
     */
    public function handle(
        TransferTableTransactionRepository $transferTableTransactionRepository,
        TransactionRepository $transactionRepository,
        TransactionItemsRepository $transactionItemsRepository,
        DbConnectionService $dbConnectionService
    ): void {
        $this->transactionRepository = $transactionRepository;
        $this->transactionItemsRepository = $transactionItemsRepository;
        $this->dbConnectionService = $dbConnectionService;

        $dbConnection = DB::connection($dbConnectionService->getConnection());
        $transferTableTransactionRepository->setConnection($dbConnectionService->getConnection());
        $this->transactionRepository->setConnection($this->dbConnectionService->getConnection());
        $this->transactionItemsRepository->setConnection($this->dbConnectionService->getConnection());

        $transactions = $transferTableTransactionRepository->fetch(['result_migration' => null]);
        if (empty($transactions->toArray())) {
            Log::info(' No transactions to migrate from Transfer table ');
        }
        foreach ($transactions as $transaction) {
            $transactionItems = $transferTableTransactionRepository->fetchTransactionItems(['ti_xref_transaction_id' => $transaction->t_transaction_id]);

            if ($transaction->result_migration !== null) {
                continue;
            }
            $dbConnection->beginTransaction();

            try {
                $response = $this->createTransactions($transaction, $transactionItems);
                if (isset($response['transaction'])) {
                    $transferTableTransactionRepository->update(['t_transaction_id' => $transaction->t_transaction_id], ['result_migration' => 'OK']);
                    Log::info('Transactions migrated successful for Transaction t_id: '.$response['transaction']['t_id']);
                }
                if (isset($response['error'])) {
                    $transferTableTransactionRepository->update(['t_transaction_id' => $transaction->t_transaction_id], ['result_migration' => json_encode($response['error'])]);
                }
                $dbConnection->commit();
            } catch (\Exception $e) {
                $dbConnection->rollBack();
                $transferTableTransactionRepository->update(['t_transaction_id' => $transaction->t_transaction_id], ['result_migration' => json_encode($e->getMessage())]);
                Log::error('Error to import Transaction ID: '.$transaction->t_transaction_id.'. Error Message: '.$e->getMessage());
            }
        }
    }

    /**
     * @param TransferTableTransaction $transferTransaction
     *
     * @return array
     */
    private function getRequestParamsForTransactions(TransferTableTransaction $transferTransaction): array
    {
        return [
            't_id' => $this->transactionRepository->getTransactionIdSeq(),
            't_xref_fg_id' => $transferTransaction->t_xref_fg_id,
            't_transaction_id' => $transferTransaction->t_transaction_id,
            't_client' => $transferTransaction->t_client,
            't_issuer' => $transferTransaction->t_issuer,
            't_gateway_transaction_id' => $transferTransaction->t_gateway_transaction_id,
            't_gateway' => $transferTransaction->t_gateway,
            't_agent_name' => $transferTransaction->t_agent_name,
            't_gateway_transaction_reference' => $transferTransaction->t_gateway_transaction_reference,
            't_status' => 'done',
            't_redirect_url' => 'eshop migration',
            't_onerror_url' => 'eshop migration',
            't_reminder_url' => 'eshop migration',
            't_callback_url' => 'eshop migration',
            't_currency' => $transferTransaction->t_currency,
            't_workflow' => 'eshop migration',
            't_tech_creation' => $transferTransaction->t_tech_creation,
            't_tech_modification' => $transferTransaction->t_tech_modification,
            't_invoice_storage' => 's3',
            't_expiration' => Carbon::parse($this->dbConnectionService->getDbNowTime())
                ->addMinutes(config('payment_gateway.expiration_minutes') ?? 60),
        ];
    }

    /**
     * @param string     $transactionId
     * @param Collection $transferTransactionItem
     *
     * @return array
     */
    private function getRequestParamsTransactionItems(string $transactionId, Collection $transferTransactionItem): array
    {
        $items = [];
        foreach ($transferTransactionItem as $transactionItem) {
            $skuData = unserialize($transactionItem->ti_transaction_item);
            $sku = $skuData['sku'] ?? array_values($skuData)[1];
            $items[] = [
                'ti_xref_f_id' => $transactionItem->ti_xref_f_id,
                'ti_xref_transaction_id' => $transactionId,
                'ti_xref_f_cai' => $transactionItem->ti_xref_f_cai,
                'ti_fee_type' => $this->getSkuTranslation($sku),
                'ti_amount' => $skuData['price'] ?? 0,
                'ti_quantity' => $skuData['quantity'] ?? 0,
                'ti_vat' => $transactionItem->ti_vat ?? 0,
                'ti_tech_creation' => $transactionItem->ti_tech_creation,
                'ti_tech_modification' => $transactionItem->ti_tech_modification,
            ];
        }

        return $items;
    }

    /**
     * @param TransferTableTransaction $transferTransaction
     * @param Collection               $transferTransactionItem
     *
     * @return array
     */
    private function createTransactions(TransferTableTransaction $transferTransaction, Collection $transferTransactionItem): array
    {
        $transactionData = $this->getRequestParamsForTransactions($transferTransaction);
        $transaction = $this->transactionRepository->create($transactionData);
        $transactionItems = $this->getRequestParamsTransactionItems($transaction->t_transaction_id, $transferTransactionItem);
        $this->transactionItemsRepository->createMany(
            $transactionItems
        );
        $basketResponse = $this->createBasketGroupItem($transferTransaction, array_first($transferTransactionItem)->ti_xref_f_id);
        if (!isset($basketResponse['transaction_id'])) {
            $responseData = [
                'error' => $basketResponse['errorBasket'] ?? 'Unknown error',
            ];
        } else {
            $responseData = [
                'transaction' => ['t_id' => $transaction->t_id, 'form_group_id' => $transaction->t_xref_fg_id],
                'baskets' => $basketResponse,
            ];
        }

        return $responseData;
    }

    /**
     * @param TransferTableTransaction $transaction
     * @param int                      $formId
     *
     * @return array|object
     */
    private function createBasketGroupItem(TransferTableTransaction $transaction, int $formId): ?array
    {
        $dbConnection = DB::connection('ecommerce_pgsql');
        $basket = [
            'application_id' => $transaction->t_xref_fg_id,
            'applicant_id' => $formId,
            'currency' => $transaction->t_currency,
            'last_modified_date' => $transaction->t_tech_modification,
        ];
        $dbConnection->table('basket')->upsert(
            $basket,
            ['application_id', 'applicant_id', 'currency'],
            ['last_modified_date']
        );

        $basketGroup = [
            'id' => $transaction->t_xref_fg_id,
            'status' => 'IN_PROGRESS',
            'creation_date' => $transaction->t_tech_creation,
            'last_modified_date' => $transaction->t_tech_modification,
            'vac_id' => substr($transaction->t_issuer, 2, 3),
            'visa_type' => $transaction->f_visa_type,
            'country_id' => substr($transaction->t_issuer, 0, 2),
        ];
        $dbConnection->table('basket_group')->upsert(
            $basketGroup,
            ['id', 'vac_id', 'visa_type', 'country_id'],
            ['last_modified_date']
        );

        return [
            'transaction_id' => $transaction->t_transaction_id,
        ];
    }

    /**
     * @param string $oldSku
     *
     * @return string
     */
    private function getSkuTranslation(string $oldSku): string
    {
        $translations = [
            'UK-MUP' => 'MUP/FR-EUR',
            'UK-SPV' => 'SPV/FR-EUR',
            'UK-CDAC-INT' => 'CDAC/FR-EUR',
            'UK-CDAC' => 'CDAC/FR-EUR',
            'UK-APPTSELFREG' => 'ASS/FR-EUR',
            'UK-ECR-3P' => 'ECR/FR-EUR',
            'UK-KMPWA' => 'KMP/FR-EUR',
            'UK-UAS' => '16107',
            'UK-PL' => 'PL/FR-EUR',
            'UK-PTA' => 'PTA/FR-EUR',
            'UK-PV' => 'PV/FR-EUR',
            'UK-SMS' => 'SMS/FR-EUR',
            'UK-WIWA' => 'WIWA/FR-EUR',
            'UK-ECR-IN' => 'ECR/FR-EUR',
            'UK-ECR-4P' => 'ECR/FR-EUR',
            '18751' => 'CDAC/FR-EUR',
            '18750' => 'CDAC/FR-EUR',
            '14872' => 'ASS/FR-EUR',
            '14866' => 'ASS/FR-EUR',
            '17056' => 'ASS/FR-EUR',
            '18952' => 'ECR/FR-EUR',
            '11978' => 'KMP/FR-EUR',
            '11139' => 'PL/FR-EUR',
            '11841' => 'PTA/FR-EUR',
            '11237' => 'PV/FR-EUR',
            '16627' => 'PV/FR-EUR',
            '12065' => 'SMS/FR-EUR',
            '12014' => 'SPV/EUR',
            '12163' => 'WIWA/FR-EUR',
            '13511' => 'ECR/FR-EUR',
            '11486' => 'ECR/FR-EUR',
            '13542' => 'ECR/FR-EUR',
            '18951' => 'ECR/FR-EUR',
        ];

        return $translations[$oldSku] ?? $oldSku;
    }
}
