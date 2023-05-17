<?php

namespace App\Console\Commands;

use App\Repositories\TransferTableTransactionRepository;
use App\Services\DbConnectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class GetTransactionsToMigrate.
 *
 * @category Console_Command
 */
class ImportTransactionsFromTlsConnect extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'import_transactions_from_tls_connect {issuer} {startDate} {endDate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import transactions from inflight applicants from TLSconnect to Payment-API';

    /**
     * @param DbConnectionService                $dbConnectionService
     * @param TransferTableTransactionRepository $transferTableTransactionRepository
     *
     * @return void
     */
    public function handle(
        DbConnectionService $dbConnectionService,
        TransferTableTransactionRepository $transferTableTransactionRepository
    ): void {
        $transferTableTransactionRepository->setConnection($dbConnectionService->getConnection());

        $filter = [
            $this->argument('startDate'),
            $this->argument('endDate'),
            $this->argument('issuer'),
        ];

        $tlsConnectDatabaseConnection = DB::connection('tlsconnect_pgsql');
        $inflightTransactions = $tlsConnectDatabaseConnection->select(
            "SELECT f.f_xref_fg_id as t_xref_fg_id,
                t.t_xref_f_id as ti_xref_f_id,
                f.f_cai as ti_xref_f_cai,
                t.t_transaction_id,
                f.f_xcopy_ug_xref_i_tag as t_issuer,
                t.t_gateway_transaction_id,
                t.t_gateway,
                a.a_result_variant as t_gateway_transaction_reference,
                t.t_currency,
                t.t_tech_creation,
                t.t_tech_modification,
                a.a_xref_u_login as t_agent_name,
                a.a_result as transaction_items,
                a.a_tech_creation as ti_tech_creation,
                a.a_tech_modification as ti_tech_modification,
                f.f_visa_type as visa_type,
                f.f_pers_csl_ref as visa_sub_type
            FROM transaction t
                INNER JOIN forms f ON (f.f_id = t.t_xref_f_id)
                JOIN actions a ON (a.a_form = f.f_id)
            WHERE f.f_is_purged IS FALSE
                AND a.a_what = 'application_support_requested'
                AND a.a_tech_deleted <> FALSE
                AND a.a_result_variant IS NOT NULL
                AND a.a_when BETWEEN :startDate AND :endDate
                AND f.f_xcopy_ug_xref_i_tag = :issuer
            ORDER BY a.a_id ASC;",
            $filter
        );

        if (empty($inflightTransactions)) {
            Log::info('No transactions found for filter: '.json_encode($filter));

            return;
        }

        $transactions = [];
        $transactionItems = [];
        foreach ($inflightTransactions as $inflightTransaction) {
            $transactionItem = [
                'ti_xref_f_id' => $inflightTransaction->ti_xref_f_id,
                'ti_xref_transaction_id' => $inflightTransaction->t_transaction_id,
                'ti_xref_f_cai' => $inflightTransaction->ti_xref_f_cai,
                'ti_transaction_item' => $inflightTransaction->transaction_items,
                'ti_tech_creation' => $inflightTransaction->ti_tech_creation,
                'ti_tech_modification' => $inflightTransaction->ti_tech_modification,
            ];

            if (isset($transactions[$inflightTransaction->t_transaction_id])) {
                $transactionItems[$inflightTransaction->t_transaction_id][] = $transactionItem;
            } else {
                $transactions[$inflightTransaction->t_transaction_id] = [
                    't_xref_fg_id' => $inflightTransaction->t_xref_fg_id,
                    't_transaction_id' => $inflightTransaction->t_transaction_id,
                    't_client' => substr($inflightTransaction->t_issuer, -2),
                    't_issuer' => $inflightTransaction->t_issuer,
                    't_gateway_transaction_id' => $inflightTransaction->t_gateway_transaction_id,
                    't_gateway' => $inflightTransaction->t_gateway,
                    't_gateway_transaction_reference' => $inflightTransaction->t_gateway_transaction_reference,
                    't_currency' => $inflightTransaction->t_currency,
                    't_tech_creation' => $inflightTransaction->t_tech_creation,
                    't_tech_modification' => $inflightTransaction->t_tech_modification,
                    't_agent_name' => $inflightTransaction->t_agent_name,
                    'f_visa_type' => $inflightTransaction->visa_type,
                    'f_visa_sub_type' => $inflightTransaction->visa_sub_type,
                ];
                $transactionItems[$inflightTransaction->t_transaction_id][] = $transactionItem;
            }
        }

        foreach ($transactions as $transaction) {
            $transactionId = $transaction['t_transaction_id'];

            $dbConnection = DB::connection($dbConnectionService->getConnection());
            $dbConnection->beginTransaction();

            try {
                $importedTransaction = $transferTableTransactionRepository->fetch(['t_transaction_id' => $transactionId]);
                if (filled($importedTransaction)) {
                    Log::warning('Import skipped for Transaction ID: '.$transactionId.'. Transaction already imported.');

                    continue;
                }

                $transferTableTransactionRepository->insertTransactionAndTransactionItems(
                    $transaction,
                    $transactionItems[$transactionId]
                );
                Log::info('Import successful for Transaction ID: '.$transactionId);

                $dbConnection->commit();
            } catch (\Exception $exception) {
                Log::error('Error to import Transaction ID: '.$transactionId.'. Error Message: '.$exception->getMessage());
                $dbConnection->rollBack();
            }
        }
    }
}
