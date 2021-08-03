<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use App\Services\TranslationService;
use Illuminate\Support\Facades\Log;

class PayBankGateway implements PaymentGatewayInterface
{
    private $transactionService;
    private $translationService;
    private $gatewayService;
    private $paymentService;

    public function __construct(
        TransactionService $transactionService,
        TranslationService $translationService,
        GatewayService $gatewayService,
        PaymentService $paymentService
    )
    {
        $this->transactionService = $transactionService;
        $this->translationService = $translationService;
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
        $lang = $params['lang'] ?? 'en';
        $transaction = $this->transactionService->getTransaction($t_id);
        $message = $this->getMessage($transaction);
        if ($message['status'] == 'error') {
            return $message;
        }
        $client  = $transaction['t_client'];
        $issuer  = $transaction['t_issuer'];
        $config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName());
        $pay_bank_config = $config['common'];
        $return_url = get_callback_url($pay_bank_config['return_url']) ?? '';
        $params = [
            't_id' => $t_id,
            'lang' => $lang,
            'redirect_url' => $transaction['t_redirect_url']
        ];
        return [
            'form_method' => 'post',
            'form_action' => $return_url,
            'form_fields' => $params,
        ];
    }

    public function return($params)
    {
        $translations = $this->translationService->getTranslation();
        $this->updatePayBankTransactionStatus($params['t_id']);
        $transaction = $this->transactionService->getTransaction($params['t_id'], $params['lang']);
        return [
            'translations' => $translations,
            'lang' => $params['lang'],
            'redirect_url' => $params['redirect_url'],
            'transaction'  => $transaction
        ];
    }

    public function notify($params) {
        $transaction_id = $params['transaction'] ?? '';
        $payment_amount = $params['amount'] ?? '';
        $currency = $params['currency'] ?? '';
        $this->paymentService->saveTransactionLog($transaction_id, $params, $this->getPaymentGatewayName());
        // find transaction in database
        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $transaction_id, 't_tech_deleted' => false]);
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
            return [
                'status' => 'fail',
                'message' => "token_not_match",
            ];
        }

        $confirm_params = [
            'gateway'        => $this->getPaymentGatewayName(),
            'amount'         => $payment_amount,
            'currency'       => $currency,
            'transaction_id' => $transaction_id,
            'gateway_transaction_id' => '',
        ];
        $response = $this->paymentService->confirm($transaction, $confirm_params);

        $return['status'] = $response['is_success'] == 'ok' ? 'success' : 'fail';
        if($return['status'] == 'fail') {
            $return['message'] = 'unknown_error';
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
            $this->transactionService->updateById($transaction['t_id'], $update_fields);
        }
    }
}
