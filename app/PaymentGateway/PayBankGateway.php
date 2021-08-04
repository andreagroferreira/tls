<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\GatewayService;
use App\Services\TransactionService;

class PayBankGateway implements PaymentGatewayInterface
{
    private $transactionService;
    private $gatewayService;

    public function __construct(
        TransactionService $transactionService,
        GatewayService $gatewayService
    )
    {
        $this->transactionService = $transactionService;
        $this->gatewayService = $gatewayService;
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

    public function notify($params)
    {
        return true;
    }

    public function redirto($params)
    {
        $t_id = $params['t_id'];
        $lang = $params['lang'] ?? 'en-us';
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
        $this->updatePayBankTransactionStatus($params['t_id']);
        $transaction = $this->transactionService->getTransaction($params['t_id']);
        return [
            'lang' => $params['lang'],
            'redirect_url' => $params['redirect_url'],
            'transaction'  => $transaction
        ];
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
