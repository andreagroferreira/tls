<?php


namespace App\Services;

use App\Jobs\PaymentEauditorLogJob;
use App\Jobs\TransactionSyncJob;
use App\Jobs\TlspayInvoiceMailJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentService
{
    protected $transactionService;
    protected $transactionLogsService;
    protected $formGroupService;
    protected $invoiceService;
    protected $apiService;
    protected $directusService;
    protected $tokenResolveService;
    protected $agent_name = '';
    protected $force_pay_for_not_online_payment_avs = 'no';//支持支付 s_online_avs=no 的avs

    public function __construct(
        TransactionService $transactionService,
        TransactionLogsService $transactionLogsService,
        FormGroupService $formGroupService,
        InvoiceService $invoiceService,
        ApiService $apiService,
        DirectusService $directusService,
        TokenResolveService $tokenResolveService
    )
    {
        $this->transactionService   = $transactionService;
        $this->transactionLogsService   = $transactionLogsService;
        $this->formGroupService     = $formGroupService;
        $this->invoiceService       = $invoiceService;
        $this->apiService  = $apiService;
        $this->directusService = $directusService;
        $this->tokenResolveService = $tokenResolveService;
    }

    public function saveTransactionLog($transaction_id, $params, $payment_gateway) {
        if (!empty($transaction_id)) {
            $this->transactionLogsService->create(['tl_xref_transaction_id' => $transaction_id, 'tl_content' => $payment_gateway ." postback:" .json_encode($params)]);
            return true;
        } else {
            Log::warning("ONLINE_PAYMENT, " . $payment_gateway . ": trying to log \$_POST info, but no orderId has been set\n\$_POST:" . json_encode($params));
            return false;
        }
    }

    public function confirm($transaction, $confirm_params)
    {
        $payment_gateway  = $confirm_params['gateway'];
        $amount_matched   = (strval($transaction['t_amount']) == strval($confirm_params['amount']));
        $currency_matched = (trim($transaction['t_currency']) == trim($confirm_params['currency']));
        $error_msg        = [];
        if (isset($confirm_params['agent_name'])) {
            $this->agent_name = $confirm_params['agent_name'];
        }
        if (isset($confirm_params['force_pay_for_not_online_payment_avs']) && $confirm_params['force_pay_for_not_online_payment_avs'] == 'yes') {
            $this->force_pay_for_not_online_payment_avs = $confirm_params['force_pay_for_not_online_payment_avs'];
        }
        if (!$amount_matched || !$currency_matched) {
            Log::warning("ONLINE PAYMENT, $payment_gateway data check failed-1 : ($amount_matched) ($currency_matched)");
            Log::warning("ONLINE PAYMENT, $payment_gateway data check failed-2 : " . json_encode($_POST, JSON_UNESCAPED_UNICODE));
            $error_msg[] = 'payment_amount_incorrect';
        }

        if ($transaction && !empty($transaction['t_items'])) {
            $actionResult = $this->syncAction($transaction, $payment_gateway);
            if(!empty($actionResult['error_msg'])) {
                $error_msg[] = $actionResult['error_msg'];
            }
        }

        $update_fields = [
            't_gateway' => $payment_gateway,
            't_gateway_transaction_id' => $confirm_params['gateway_transaction_id'],
            't_status' => 'done',
            't_gateway_account' => $confirm_params['t_gateway_account'] ?? null,
            't_gateway_subaccount' => $confirm_params['t_gateway_subaccount'] ?? null,
        ];
        $this->transactionService->updateById($transaction['t_id'], $update_fields);
        foreach ($update_fields as $field_key => $field_val) {
            $transaction[$field_key] = $field_val;
        }
        $this->invoiceService->generate($transaction);

        $issuer = $transaction['t_issuer'];
        $content = $this->getInvoiceContent('tlspay_email_invoice', $issuer);
        $resolved_content = $this->tokenResolveService->resolveTemplate($content, $issuer);
        if (!empty($resolved_content)) {
            //Generate PDF to place in Filelibrary
            //Code will be added by Aditya
            
            //Send email
            $this->sendInvoice($transaction, $resolved_content, 'tlspay_invoice_queue');
        }

        if(!empty($error_msg)) {
            Log::error('Transaction ERROR: transaction ' . $transaction['t_transaction_id'] . ' failed, because: ' . json_encode($error_msg, 256));
            $show_error_msg = 'Transaction ERROR: transaction ' . $transaction['t_transaction_id'] . ' failed';
        }
        $result =  [
            'is_success' => empty($error_msg) ? 'ok' : 'error',
            'orderid' => $transaction['t_transaction_id'],
            'issuer' => $transaction['t_issuer'],
            'amount' => $transaction['t_amount'],
            'message' => empty($error_msg) ? 'Transaction OK: transaction has been confirmed' : $show_error_msg,
            'href' => $transaction['t_redirect_url']
        ];

        $this->transactionLogsService->create(['tl_xref_transaction_id' => $transaction['t_transaction_id'], 'tl_content' =>json_encode($result)]);
        return $result;
    }

    private function getInvoiceContent($collection_name, $issuer) {
        $content = [];

        $country = substr($issuer, 0, 2);
        $city = substr($issuer, 2, 3);

        $select_filters = [
            'status' => [
                'eq' => 'published'
            ],
            'code' => [
                'in' => [$city, $country, 'ww']
            ],
        ];
        $select_fields = '*.*';
        $content = $this->directusService->getContent($collection_name, $select_fields, $select_filters, $options = ['lang'=> 'en-us']);

        return $content;
    }

    private function sendInvoice($transaction, $resolved_content, $queue_name) {
        $fg_id = $transaction['t_xref_fg_id'];
        $client = $transaction['t_client'];

        $form_group = $this->formGroupService->fetch($fg_id, $client);
        $form_user_email = $form_group['u_email'] ?? '';
        if (!empty($form_user_email)) {
            if (!empty($resolved_content['email_content']) && !empty($resolved_content['invoice_content'])) {
                //Prepare email content
                $email_body = [
                    'to' => $form_user_email,
                    'subject' => $resolved_content['email_title'],
                    'body' => $resolved_content['email_content'],
                    'html2pdf' => [
                        'invoice' => $resolved_content['invoice_content']
                    ]
                ];

                //Add email job to queue
                dispatch(new TlspayInvoiceMailJob($email_body))->onConnection($queue_name)->onQueue($queue_name);
            }
        }
    }

    private function syncAction($transaction, $gateway)
    {
        $client = $transaction['t_client'];
        $formGroupInfo = $this->formGroupService->fetch($transaction['t_xref_fg_id'], $client);
        if(empty($formGroupInfo)) {
            return [
                'status'    => 'error',
                'error_msg' => 'form_group_not_found'
            ];
        }
        $data = [
            'gateway' => $gateway,
            'u_id' => !empty($formGroupInfo['fg_xref_u_id']) ? $formGroupInfo['fg_xref_u_id'] : 0,
            't_items' => $transaction['t_items'],
            't_transaction_id' => $transaction['t_transaction_id'],
            't_issuer' => $transaction['t_issuer'],
            't_currency' => $transaction['t_currency']
        ];
        if ($this->agent_name) {
            $data['agent_name'] = $this->agent_name;
        }
        if ($this->force_pay_for_not_online_payment_avs == 'yes') {
            $data['force_pay_for_not_online_payment_avs'] = $this->force_pay_for_not_online_payment_avs;
        }
        Log::info('paymentservice syncAction start');
        try {
            dispatch(new TransactionSyncJob($client, $data))->onConnection('tlscontact_transaction_sync_queue')->onQueue('tlscontact_transaction_sync_queue');
            Log::info('paymentservice syncAction:dispatch');
            return [
                'error_msg' => []
            ];
        } catch (\Exception $e) {
            Log::info('paymentservice syncAction dispatch error_msg:'.$e->getMessage());
            return [
                'status'    => 'error',
                'error_msg' => $e->getMessage()
            ];
        }
    }

    public function sendEAuditorProfileLogs($data): bool
    {
        $eauditor_log_content = $this->formatProfileData($data);
        //send eauditor log
        $this->apiService->callEAuditorApi('POST', env('TLSCONTACT_EAUDITOR_PORT'), $eauditor_log_content);
        return true;
    }

    private function formatProfileData($data): array
    {
        $result = array();
        $result['timestamp']    = Carbon::now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.v\Z');
        $result['policy']       = 'audit';
        $result['tags']         = 'tech';
        $result['domain']       = 'emetrics';
        $result['project']      = 'TLSpay';
        $result['service']      = 'Profiling';
        $result['user.login']   = $data['user_name'];
        $result['action'] = array();
        $result['action']['name']       = $data['action_name'];
        $result['action']['timestamp']  = Carbon::now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.v\Z');
        $result['action']['result']     = $data['rc_file_name'] ?? 'No filename provided';
        $result['action']['comment']    = $data['errorComment'] ?? 'Rule engine uploaded successful';
        $result['action']['type']       = $data['type'] ?? '';
        $result['client'] = array();
        $result['client']['code'] = $this->apiService->getProjectId();
        $result['message'] = array();
        $result['message']['type'] = 'profiling';

        return $result;
    }


    public function PaymentTransationBeforeLog($service, $data)
    {
        $data['comment'] = 'Transfered to ' . $service;
        dispatch(new PaymentEauditorLogJob($data))->onConnection('payment_api_eauditor_log_queue')->onQueue('payment_api_eauditor_log_queue');
    }

    public function PaymentTransactionCallbackLog($service, $data, $response, $comment)
    {
        $message = ['json'=>['products'=>$data['t_items']],'text'=>json_encode($response)];
        $data['t_items'] = $message;
        $comments = $comment == 'success' ? "Payment done on {$service}" : "Payment failed on {$service}";
        $data['comment'] = $comments;
        dispatch(new PaymentEauditorLogJob($data))->onConnection('payment_api_eauditor_log_queue')->onQueue('payment_api_eauditor_log_queue');
    }

    public function sendPaymentTransationLogs($data): bool
    {
        $result = array();
        $result['timestamp']    = Carbon::now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.v\Z');
        $result['policy']       = 'audit';
        $result['tags']         = 'tech';
        $result['domain']       = 'emetrics';
        $result['project']      = 'TLSpay';
        $result['service']      = 'PaymentGatewayApp';
        $result['city']         = substr($data['t_issuer'], 2, 3);
        $result['city_name']    = getCityName($result['city']);
        $result['country']      = substr($data['t_issuer'], 0, 2);
        $result['country_name'] = getCountryName($result['country']);
        $result['message']      = $data['t_items'] ?? '';
        $result['action'] = array();
        $result['action']['result']     = $data['t_transaction_id'] ?? '';
        $result['action']['comment']    = $data['comment'];
        $result['action']['name']       = 'PaymentGatewayTrasnfer';
        $result['action']['timestamp']  = Carbon::now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.v\Z');
        $result['client'] = array();
        $result['client']['code'] = $data['t_client'];
        $result['reference'] = array();
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

        $result = array();
        $result['timestamp']    = Carbon::now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.v\Z');
        $result['policy']       = 'audit';
        $result['tags']         = 'tech';
        $result['domain']       = 'emetrics';
        $result['project']      = 'TLSpay';
        $result['service']      = 'TLSpayServiceApp';
        $result['city']         = substr($translations['t_issuer'], 2, 3);
        $result['city_name']    = getCityName($result['city']);
        $result['country']      = substr($translations['t_issuer'], 0, 2);
        $result['country_name'] = getCountryName($result['country']);
        $result['message']      = $translations['t_items'] ?? '';
        $result['action'] = array();
        $result['action']['result']     = $translations['t_transaction_id'] ?? '';
        $result['action']['comment']    = 'Order created';
        $result['action']['name']       = 'OrderCreation';
        $result['action']['timestamp']  = Carbon::now()->setTimezone('UTC')->format('Y-m-d\TH:i:s.v\Z');
        $result['client'] = array();
        $result['client']['code'] = $translations['t_client'];
        $result['reference'] = array();
        $result['reference']['id'] = $translations['t_xref_fg_id'];

        $this->apiService->callEAuditorApi('POST', env('TLSCONTACT_EAUDITOR_PORT'), $result);
        return true;
    }
}
