<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\DbConnectionService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PayBankGateway implements PaymentGatewayInterface
{
    private $transactionService;
    private $gatewayService;
    private $paymentService;

    public function __construct(
        TransactionService $transactionService,
        GatewayService $gatewayService,
        PaymentService $paymentService
    )
    {
        $this->transactionService = $transactionService;
        $this->gatewayService = $gatewayService;
        $this->paymentService = $paymentService;
    }

    public function getPaymentGatewayName()
    {
        return 'pay_bank';
    }

    public function isSandBox()
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    public function checkout()
    {
        return true;
    }

    public function redirto($params)
    {
        $t_id = $params['t_id'];
        $pa_id = $params['pa_id'] ?? null;
        $lang = $params['lang'] ?? 'en-us';
        $transaction = $this->transactionService->getTransaction($t_id);
        if (blank($transaction)) {
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: transaction not found'
            ];
        } else if ($pa_id) {
            $this->transactionService->updateById($t_id, ['t_xref_pa_id' => $pa_id]);
        }
        $client  = $transaction['t_client'];
        $issuer  = $transaction['t_issuer'];
        $config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $pa_id);
        $pay_bank_config = $config['common'];
        $return_url = get_callback_url($pay_bank_config['return_url']) ?? '';
        $params = [
            't_id' => $t_id,
            'lang' => $lang,
            'redirect_url' => $transaction['t_redirect_url']
        ];

        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $transaction);

        return [
            'form_method' => 'post',
            'form_action' => $return_url,
            'form_fields' => $params,
        ];
    }

    public function return($params)
    {
        $this->updatePayBankTransactionStatus($params['t_id']);
        $transaction = $this->transactionService->getTransaction($params['t_id']);
        return [
            'lang' => $params['lang'],
            'redirect_url' => $params['redirect_url'],
            'transaction'  => $transaction
        ];
    }

    public function notify($params) {
        $transaction_id = $params['transaction'] ?? '';
        $payment_amount = $params['amount'] ?? '';
        $currency = $params['currency'] ?? '';
        // find transaction in database
        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $transaction_id, 't_tech_deleted' => false]);
        $paymentMethod = $transaction['t_payment_method'] ? $transaction['t_payment_method'] : $this->getPaymentGatewayName();
        $this->paymentService->saveTransactionLog($transaction_id, $params, $paymentMethod);
        if (empty($transaction)) {
            Log::warning("ONLINE PAYMENT, BANK PAYMENT: notify check failed : the transaction number $transaction_id does not exist");
            return [
                'status' => 'fail',
                'message' => "transaction_id_not_exists",
            ];
        }

        //verify token
        $secret = env('BANK_PAYMENT_SECRET');
        $tmp = "$transaction_id.$payment_amount.$currency";
        $hashTmp = hash('sha256', $tmp) . $secret;
        $sha512hash = hash('sha256', $hashTmp);
        $realHash = $params['token'] ?? '';
        if ($sha512hash != $realHash) {
            Log::warning("ONLINE PAYMENT, BANK PAYMENT: notify check failed : the token $transaction_id is not matched");
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'fail');
            return [
                'status' => 'fail',
                'message' => "token_not_match",
            ];
        }

        $confirm_params = [
            'gateway'        => $paymentMethod,
            'amount'         => $payment_amount,
            'currency'       => $currency,
            'transaction_id' => $transaction_id,
            'gateway_transaction_id' => '',
        ];
        if (isset($params['agent_name'])) {
            $confirm_params['agent_name'] = $params['agent_name'];
        }
        if (isset($params['force_pay_for_not_online_payment_avs']) && $params['force_pay_for_not_online_payment_avs'] == 'yes') {
            $confirm_params['force_pay_for_not_online_payment_avs'] = $params['force_pay_for_not_online_payment_avs'];
        }
        $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'success');
        $response = $this->paymentService->confirm($transaction, $confirm_params);

        $return['status'] = $response['is_success'] == 'ok' ? 'success' : 'fail';
        if($return['status'] == 'fail') {
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'fail');
            $return['message'] = $response['message'];
        }
        return $return;
    }

    private function getMessage($transaction)
    {
        $error = false;
        $msg = '';
        if (empty($transaction)) {
            $error = true;
            $msg = 'transaction_id_not_exists';
        } else if ($transaction['t_gateway'] == 'pay_bank' && $transaction['t_status'] == 'pending') {
            $error = true;
            $msg = 'pay_bank_has_been_chosen';
        } else if ($transaction['t_gateway'] != 'pay_bank' && $transaction['t_status'] == 'done') {
            $error = true;
            $msg = 'transaction_done_by_other_gateway';
        }
        $return['status'] = $error ? 'error' : 'ok';
        if($error) {
            $return['msg'] = $msg;
        }
        return $return;
    }

    private function updatePayBankTransactionStatus($t_id)
    {
        $transaction = $this->transactionService->getTransaction($t_id);
        $paymentWay = $this->getPaymentGatewayName();
        if ($transaction['t_gateway'] != $paymentWay || $transaction['t_status'] == 'pending') {
            $gateway_transaction_id = 'PAY-BANK-' . date('His') . '-' . ($transaction['t_transaction_id'] ?? random_int(1000, 9999));
            $update_fields = [
                't_gateway' => $paymentWay,
                't_gateway_transaction_id' => $gateway_transaction_id
            ];
            $client  = $transaction['t_client'];
            $issuer  = $transaction['t_issuer'];
            $config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $transaction['t_xref_pa_id']);
            if(!empty($config['common']['expiration_minutes'])) {
                $now_time = (new DbConnectionService())->getDbNowTime();
                $gateway_expiration = Carbon::parse($now_time)->addMinutes($config['common']['expiration_minutes']);
                $update_fields['t_gateway_expiration'] = $gateway_expiration;
                $update_fields['t_expiration'] = $gateway_expiration;
            }
            $this->transactionService->updateById($transaction['t_id'], $update_fields);
        }
    }
}
