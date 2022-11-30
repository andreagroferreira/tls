<?php

namespace App\Services;

use App\Jobs\InvoiceMailJob;
use App\Jobs\PaymentEauditorLogJob;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $transactionService;
    protected $transactionLogsService;
    protected $formGroupService;
    protected $invoiceService;
    protected $apiService;
    protected $tokenResolveService;
    protected $agent_name = '';
    protected $force_pay_for_not_online_payment_avs = 'no'; //支持支付 s_online_avs=no 的avs

    public function __construct(
        TransactionService $transactionService,
        TransactionLogsService $transactionLogsService,
        FormGroupService $formGroupService,
        InvoiceService $invoiceService,
        ApiService $apiService,
        TokenResolveService $tokenResolveService
    ) {
        $this->transactionService = $transactionService;
        $this->transactionLogsService = $transactionLogsService;
        $this->formGroupService = $formGroupService;
        $this->invoiceService = $invoiceService;
        $this->apiService = $apiService;
        $this->tokenResolveService = $tokenResolveService;
    }

    public function saveTransactionLog(
        $transaction_id,
        $params,
        $payment_gateway
    ) {
        if (!empty($transaction_id)) {
            $this->transactionLogsService->create(['tl_xref_transaction_id' => $transaction_id, 'tl_content' => $payment_gateway.' postback:'.json_encode($params)]);

            return true;
        }
        Log::warning('ONLINE_PAYMENT, '.$payment_gateway.": trying to log \$_POST info, but no orderId has been set\n\$_POST:".json_encode($params));

        return false;
    }

    /**
     * @param array $transaction
     * @param array $confirm_params
     *
     * @return array
     */
    public function confirm(array $transaction, array $confirm_params): array
    {
        $payment_gateway = $confirm_params['gateway'];
        $amount_matched = (strval($transaction['t_amount']) == strval($confirm_params['amount']));
        $currency_matched = (trim($transaction['t_currency']) == trim($confirm_params['currency']));
        $error_msg = [];
        if (isset($confirm_params['agent_name'])) {
            $this->agent_name = $confirm_params['agent_name'];
        }
        if (isset($confirm_params['force_pay_for_not_online_payment_avs']) && $confirm_params['force_pay_for_not_online_payment_avs'] === 'yes') {
            $this->force_pay_for_not_online_payment_avs = $confirm_params['force_pay_for_not_online_payment_avs'];
        }
        if (!$amount_matched || !$currency_matched) {
            Log::warning("ONLINE PAYMENT, {$payment_gateway} data check failed-1 : ({$amount_matched}) ({$currency_matched})");
            Log::warning("ONLINE PAYMENT, {$payment_gateway} data check failed-2 : ".json_encode($_POST, JSON_UNESCAPED_UNICODE));
            $error_msg[] = 'payment_amount_incorrect';
        }

        if ($transaction && !empty($transaction['t_items'])) {
            if (!empty($transaction['t_xref_fg_id'])) {
                $actionResult = $this->transactionService->syncTransaction(
                    $transaction,
                    $payment_gateway,
                    $this->agent_name,
                    $this->force_pay_for_not_online_payment_avs
                );
                if (!empty($actionResult['error_msg'])) {
                    $error_msg[] = $actionResult['error_msg'];
                }
            }
        }

        $update_fields = [
            't_gateway' => $payment_gateway,
            't_gateway_transaction_id' => $confirm_params['gateway_transaction_id'],
            't_status' => 'done',
            't_gateway_account' => $confirm_params['t_gateway_account'] ?? null,
            't_gateway_subaccount' => $confirm_params['t_gateway_subaccount'] ?? null,
            't_invoice_storage' => $confirm_params['t_invoice_storage'] ?? 'file-library',
        ];

        $this->transactionService->updateById($transaction['t_id'], $update_fields);
        foreach ($update_fields as $field_key => $field_val) {
            $transaction[$field_key] = $field_val;
        }

        dispatch(new InvoiceMailJob($transaction, 'tlspay_email_invoice'))
            ->onConnection('tlspay_invoice_queue')->onQueue('tlspay_invoice_queue');

        if (!empty($error_msg)) {
            Log::error('Transaction ERROR: transaction '.$transaction['t_transaction_id'].' failed, because: '.json_encode($error_msg, 256));
            $show_error_msg = 'Transaction ERROR: transaction '.$transaction['t_transaction_id'].' failed';
        }
        $result = [
            'is_success' => empty($error_msg) ? 'ok' : 'error',
            'orderid' => $transaction['t_transaction_id'],
            'issuer' => $transaction['t_issuer'],
            'amount' => $transaction['t_amount'],
            'message' => empty($error_msg) ? 'Transaction OK: transaction has been confirmed' : $show_error_msg,
            'href' => $transaction['t_redirect_url'],
        ];

        $this->transactionLogsService->create(['tl_xref_transaction_id' => $transaction['t_transaction_id'], 'tl_content' => json_encode($result)]);

        return $result;
    }

    public function sendEAuditorProfileLogs($data): bool
    {
        $eauditor_log_content = $this->formatProfileData($data);
        //send eauditor log
        $this->apiService->callEAuditorApi('POST', env('TLSCONTACT_EAUDITOR_PORT'), $eauditor_log_content);

        return true;
    }

    public function PaymentTransationBeforeLog($service, $data)
    {
        $data['comment'] = 'Transfered to '.$service;
        dispatch(new PaymentEauditorLogJob($data))->onConnection('payment_api_eauditor_log_queue')->onQueue('payment_api_eauditor_log_queue');
    }

    public function PaymentTransactionCallbackLog($service, $data, $response, $comment)
    {
        $message = ['json' => ['products' => $data['t_items']], 'text' => json_encode($response)];
        $data['t_items'] = $message;
        $comments = $comment === 'success' ? "Payment done on {$service}" : "Payment failed on {$service}";
        $data['comment'] = $comments;
        dispatch(new PaymentEauditorLogJob($data))->onConnection('payment_api_eauditor_log_queue')->onQueue('payment_api_eauditor_log_queue');
    }

    public function sendPaymentTransationLogs($data): bool
    {
        $result = [];
        $result['timestamp'] = Carbon::now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.v\Z');
        $result['policy'] = 'audit';
        $result['tags'] = 'tech';
        $result['domain'] = 'emetrics';
        $result['project'] = 'TLSpay';
        $result['service'] = 'PaymentGatewayApp';
        $result['city'] = substr($data['t_issuer'], 2, 3);
        $result['city_name'] = getCityName($result['city']);
        $result['country'] = substr($data['t_issuer'], 0, 2);
        $result['country_name'] = getCountryName($result['country']);
        $result['message'] = $data['t_items'] ?? '';
        $result['action'] = [];
        $result['action']['result'] = $data['t_transaction_id'] ?? '';
        $result['action']['comment'] = $data['comment'];
        $result['action']['name'] = 'PaymentGatewayTrasnfer';
        $result['action']['timestamp'] = Carbon::now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.v\Z');
        $result['client'] = [];
        $result['client']['code'] = $data['t_client'];
        $result['reference'] = [];
        $result['reference']['id'] = $data['t_xref_fg_id'];

        $this->apiService->callEAuditorApi('POST', env('TLSCONTACT_EAUDITOR_PORT'), $result);

        return true;
    }

    public function sendCreatePaymentOrderLogs($data): bool
    {
        if (isset($data['t_id'])) {
            $t_id = $data['t_id'];
        } else {
            return false;
        }
        $translations = $this->transactionService->getTransaction($t_id);

        $result = [];
        $result['timestamp'] = Carbon::now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.v\Z');
        $result['policy'] = 'audit';
        $result['tags'] = 'tech';
        $result['domain'] = 'emetrics';
        $result['project'] = 'TLSpay';
        $result['service'] = 'TLSpayServiceApp';
        $result['city'] = substr($translations['t_issuer'], 2, 3);
        $result['city_name'] = getCityName($result['city']);
        $result['country'] = substr($translations['t_issuer'], 0, 2);
        $result['country_name'] = getCountryName($result['country']);
        $result['message'] = $translations['t_items'] ?? '';
        $result['action'] = [];
        $result['action']['result'] = $translations['t_transaction_id'] ?? '';
        $result['action']['comment'] = 'Order created';
        $result['action']['name'] = 'OrderCreation';
        $result['action']['timestamp'] = Carbon::now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.v\Z');
        $result['client'] = [];
        $result['client']['code'] = $translations['t_client'];
        $result['reference'] = [];
        $result['reference']['id'] = $translations['t_xref_fg_id'];

        $this->apiService->callEAuditorApi('POST', env('TLSCONTACT_EAUDITOR_PORT'), $result);

        return true;
    }

    /**
     * @param array  $transaction
     * @param string $invoice_content
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     * @return bool
     */
    public function convertInvoiceContentToPdf(array $transaction, string $invoice_content): bool
    {
        $scope = $transaction['t_xref_fg_id'];
        $country = substr($transaction['t_issuer'], 0, 2);
        $city = substr($transaction['t_issuer'], 2, 3).'/'.$scope;
        $fileName = $transaction['t_transaction_id'].'.pdf';
        $userName = 'tlspay';
        $queryParams = 'country='.$country.'&city='.$city.'&fileName='.$fileName.'&userName='.$userName;

        $pdf = Pdf::loadHTML($invoice_content);
        $pdfstream = $pdf->download($fileName);
        $response = $this->apiService->callFileLibraryUploadApi($queryParams, $pdfstream);
        unset($pdfstream);

        if ($response['status'] !== 200) {
            Log::warning('Transaction Error: receipt pdf upload failed');

            return false;
        }

        return true;
    }

    /**
     * @param array  $transaction
     * @param string $collection_name
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     * @return void
     */
    public function sendInvoice(array $transaction, string $collection_name): void
    {
        $callback_url = $transaction['t_callback_url'];
        $lang = 'en-us';
        if ($callback_url) {
            $url_query_string = parse_url($callback_url, PHP_URL_QUERY);
            parse_str($url_query_string, $url_query_string_to_array);

            if (!empty($url_query_string_to_array['lang'])) {
                $lang = $url_query_string_to_array['lang'];
            }
        }

        $content = $this->invoiceService->getInvoiceContent(
            $collection_name,
            $transaction['t_issuer'],
            $transaction['t_service'],
            $lang
        );

        if (empty($content)) {
            throw new \Exception('Error Fetching Invoice Content');
        }

        $resolved_content = $this->tokenResolveService->resolveTemplate(
            $content,
            $transaction,
            $lang
        );

        if (empty($resolved_content)) {
            throw new \Exception('Error Resolving Invoice Content');
        }

        $response = $this->convertInvoiceContentToPdf($transaction, $resolved_content['invoice_content']);

        if (!$response) {
            throw new \Exception('Error Processing Invoice Upload Request');
        }

        $this->invoiceService->sendInvoice(
            $transaction['t_xref_fg_id'],
            $transaction['t_client'],
            $resolved_content
        );
    }

    private function formatProfileData($data): array
    {
        $result = [];
        $result['timestamp'] = Carbon::now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.v\Z');
        $result['policy'] = 'audit';
        $result['tags'] = 'tech';
        $result['domain'] = 'emetrics';
        $result['project'] = 'TLSpay';
        $result['service'] = 'Profiling';
        $result['user.login'] = $data['user_name'];
        $result['action'] = [];
        $result['action']['name'] = $data['action_name'];
        $result['action']['timestamp'] = Carbon::now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.v\Z');
        $result['action']['result'] = $data['rc_file_name'] ?? 'No filename provided';
        $result['action']['comment'] = $data['errorComment'] ?? 'Rule engine uploaded successful';
        $result['action']['type'] = $data['type'] ?? '';
        $result['client'] = [];
        $result['client']['code'] = $this->apiService->getProjectId();
        $result['message'] = [];
        $result['message']['type'] = 'profiling';

        return $result;
    }
}
