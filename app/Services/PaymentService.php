<?php


namespace App\Services;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $transactionService;
    protected $transactionLogsService;
    protected $formGroupService;
    protected $invoiceService;
    protected $apiService;
    protected $agent_name = '';
    protected $force_pay_for_not_online_payment_avs = 'no';//支持支付 s_online_avs=no 的avs

    public function __construct(
        TransactionService $transactionService,
        TransactionLogsService $transactionLogsService,
        FormGroupService $formGroupService,
        InvoiceService $invoiceService,
        ApiService $apiService
    )
    {
        $this->transactionService   = $transactionService;
        $this->transactionLogsService   = $transactionLogsService;
        $this->formGroupService     = $formGroupService;
        $this->invoiceService       = $invoiceService;
        $this->apiService  = $apiService;
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

        $update_fields       = [
            't_gateway'                => $payment_gateway,
            't_gateway_transaction_id' => $confirm_params['gateway_transaction_id'],
            't_status'                 => 'done'
        ];
        $this->transactionService->updateById($transaction['t_id'], $update_fields);
        foreach ($update_fields as $field_key => $field_val) {
            $transaction[$field_key] = $field_val;
        }
        $this->invoiceService->generate($transaction);

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
            't_issuer' => $transaction['t_issuer']
        ];
        if ($this->agent_name) {
            $data['agent_name'] = $this->agent_name;
        }
        if ($this->force_pay_for_not_online_payment_avs == 'yes') {
            $data['force_pay_for_not_online_payment_avs'] = $this->force_pay_for_not_online_payment_avs;
        }
        $response = $this->apiService->callTlsApi('POST', '/tls/v1/' . $client . '/sync_payment_action', $data);

        if($response['status'] == 200){
            return $response['body'];
        } else {
            return [
                'status'    => 'error',
                'error_msg' => $response['body']['message']
            ];
        }
    }
}
