<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\PaymentService;
use App\Services\TransactionService;

class PayLaterGateway implements PaymentGatewayInterface
{
    private $transactionService;
    private $paymentService;

    public function __construct(
        TransactionService $transactionService,
        PaymentService $paymentService
    )
    {
        $this->transactionService = $transactionService;
        $this->paymentService     = $paymentService;
    }

    public function getPaymentGatewayName()
    {
        return 'pay_later';
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
        $lang = $params['lang'] ?? 'en';
        $transaction = $this->transactionService->getTransaction($t_id);
        $message = $this->getMessage($transaction);
        if ($message['status'] == 'error') {
            return $message;
        }
        $config =  config('payment_gateway')[$this->getPaymentGatewayName()];
        $return_url = get_callback_url($config['return_url']) ?? '';
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
        $this->updatePayLaterTransactionStatus($params['t_id']);
        return [
            'lang' => $params['lang'],
            'redirect_url' => $params['redirect_url']
        ];
    }

    private function getMessage($transaction) {
        if (empty($transaction)) {
            return [
                'status'  => 'error',
                'msg' => 'transaction_id_not_exists'
            ];
        } else if ($transaction['t_gateway'] == 'pay_later' && $transaction['t_status'] == 'pending') {
            return [
                'status'  => 'error',
                'msg' => 'pay_later_has_beeen_choosen',
            ];
        } else if ($transaction['t_gateway'] != 'pay_later' && $transaction['t_status'] == 'done') {
            return [
                'status'  => 'error',
                'msg' => 'transaction_done_by_other_gateway',
            ];
        } else {
            return ['status'  => 'ok'];
        }
    }

    private function updatePayLaterTransactionStatus($t_id) {
        $transaction = $this->transactionService->getTransaction($t_id);
        $paymentWay = $this->getPaymentGatewayName();
        if ($transaction['t_gateway'] != $paymentWay || $transaction['t_status'] == 'pending') {
            $gateway_transaction_id = 'PAY-LATER-' . date('His') . '-' . ($transaction['t_transaction_id'] ?? random_int(1000, 9999));
            $update_fields = [
                't_gateway' => $paymentWay,
                't_gateway_transaction_id' => $gateway_transaction_id
            ];
            return $this->transactionService->updateById($transaction['t_id'], $update_fields);
        }
    }
}
