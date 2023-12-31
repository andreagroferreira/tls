<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\PaymentService;
use App\Services\TransactionService;
use App\Traits\FeatureVersionsTrait;
use Illuminate\Support\Facades\Log;

class PayLaterGateway implements PaymentGatewayInterface
{
    use FeatureVersionsTrait;

    private $transactionService;
    private $paymentService;

    public function __construct(
        TransactionService $transactionService,
        PaymentService $paymentService
    ) {
        $this->transactionService = $transactionService;
        $this->paymentService = $paymentService;
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
        $config = config('payment_gateway')[$this->getPaymentGatewayName()];
        $return_url = get_callback_url($config['return_url']) ?? '';
        $params = [
            't_id' => $t_id,
            'lang' => $lang,
            'redirect_url' => $transaction['t_redirect_url'],
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
        $transaction = $this->transactionService->getTransaction($params['t_id']);

        $this->updatePayLaterTransactionStatus($transaction);

        if (!$this->isVersion(1, $transaction['t_issuer'], 'transaction_sync')) {
            $this->syncTransactionToWorkflowService($transaction);
            $this->syncTransactionToEcommerce($transaction);
        }

        return [
            'lang' => $params['lang'],
            'redirect_url' => $params['redirect_url'],
        ];
    }

    private function getMessage($transaction)
    {
        if (empty($transaction)) {
            return [
                'status' => 'error',
                'msg' => 'transaction_id_not_exists',
            ];
        }
        if ($transaction['t_gateway'] == 'pay_later' && $transaction['t_status'] == 'pending') {
            return [
                'status' => 'error',
                'msg' => 'pay_later_has_beeen_choosen',
            ];
        }
        if ($transaction['t_gateway'] != 'pay_later' && $transaction['t_status'] == 'done') {
            return [
                'status' => 'error',
                'msg' => 'transaction_done_by_other_gateway',
            ];
        }

        return ['status' => 'ok'];
    }

    private function updatePayLaterTransactionStatus(array $transaction)
    {
        $paymentWay = $this->getPaymentGatewayName();
        if ($transaction['t_gateway'] != $paymentWay || $transaction['t_status'] == 'pending') {
            $gateway_transaction_id = 'PAY-LATER-' . date('His') . '-' . ($transaction['t_transaction_id'] ?? random_int(1000, 9999));
            $update_fields = [
                't_gateway' => $paymentWay,
                't_gateway_transaction_id' => $gateway_transaction_id,
            ];

            return $this->transactionService->updateById($transaction['t_id'], $update_fields);
        }
    }

    /**
     * @param array $transaction
     *
     * @return void
     */
    private function syncTransactionToEcommerce(array $transaction): void
    {
        $ecommerceSyncStatus = $this->transactionService->syncTransactionToEcommerce($transaction, 'PAY_LATER');
        if (!empty($ecommerceSyncStatus['error_msg'])) {
            Log::error(
                'Transaction ERROR: transaction sync to ecommerce ' .
                $transaction['t_transaction_id'] . ' failed, because: ' .
                json_encode($ecommerceSyncStatus, 256)
            );
        }
    }

    /**
     * @param array $transaction
     *
     * @return void
     */
    private function syncTransactionToWorkflowService(array $transaction): void
    {
        $workflowServiceSyncStatus = $this->transactionService->syncTransactionToWorkflow($transaction);
        if (!empty($workflowServiceSyncStatus['error_msg'])) {
            Log::error(
                'Transaction ERROR: transaction ' .
                $transaction['t_transaction_id'] . ' failed, because: ' .
                json_encode($workflowServiceSyncStatus['error_msg'], 256)
            );
        }
    }
}
