<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\FormGroupService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;

class PaysoftPaymentGateway implements PaymentGatewayInterface
{
    protected $transactionService;
    protected $gatewayService;
    protected $paymentService;
    protected $formGroupService;
    protected $amount_decimals = 2;

    public function __construct(
        TransactionService $transactionService,
        GatewayService $gatewayService,
        PaymentService $paymentService,
        FormGroupService $formGroupService
    ) {
        $this->transactionService = $transactionService;
        $this->gatewayService = $gatewayService;
        $this->paymentService = $paymentService;
        $this->formGroupService = $formGroupService;
    }

    public function getPaymentGatewayName()
    {
        return 'paysoft';
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
        $translations_data = $this->transactionService->getTransaction($t_id);
        if (blank($translations_data)) {
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: transaction not found',
            ];
        }
        if ($pa_id) {
            $this->transactionService->updateById($t_id, ['t_xref_pa_id' => $pa_id]);
        }

        $client = $translations_data['t_client'];
        $order_id = $translations_data['t_transaction_id'] ?? '';

        $fg_id = $translations_data['t_xref_fg_id'];
        $application_group = $this->formGroupService->fetch($fg_id, $client);
        $u_email = array_get($application_group, 'u_relative_email') ?? array_get($application_group, 'u_email') ?? "tlspay-{$client}-{$fg_id}@tlscontact.com";

        $paysoft_config = $this->getConfig($client, $translations_data['t_issuer'], $pa_id);
        $return_url = get_callback_url(array_get($paysoft_config, 'common.return_url'));

        $form_fields['LMI_MERCHANT_ID'] = array_get($paysoft_config, 'current.merchant_id');
        $form_fields['LMI_PAYMENT_AMOUNT'] = $this->amountFormat($translations_data['t_amount']);
        $form_fields['LMI_PAYMENT_NO'] = $order_id;
        $form_fields['LMI_PAYMENT_DESC'] = "Order {$translations_data['t_transaction_id']} - {$u_email}";
        $form_fields['LMI_PAYER_EMAIL'] = $u_email;
        $form_fields['LMI_SUCCESS_URL'] = $return_url;
        $form_fields['LMI_FAIL_URL'] = $return_url;
        $form_fields['LMI_HASH'] = $this->generateSignature($paysoft_config, [$form_fields['LMI_MERCHANT_ID'], $order_id, $form_fields['LMI_PAYMENT_AMOUNT']]);

        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $translations_data);

        return [
            'form_method' => 'post',
            'form_action' => array_get($paysoft_config, 'current.host'),
            'form_fields' => $form_fields,
        ];
    }

    public function notify($params)
    {
        $order_id = array_get($params, 'LMI_PAYMENT_NO');
        $this->paymentService->saveTransactionLog($order_id, $params, $this->getPaymentGatewayName());

        if (!array_has($params, 'LMI_SYS_PAYMENT_ID') && !array_has($params, 'LMI_HASH')) {
            return $this->initialNotify($params);
        }

        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $order_id, 't_tech_deleted' => false]);
        if (strtolower(array_get($transaction, 't_status')) != 'pending') {
            $this->logWarning('notify data check failed, incorrect order status.', $params);
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(), $transaction, $params, 'fail');

            return false;
        }

        if (bccomp($this->amountFormat($transaction['t_amount']), array_get($params, 'LMI_PAYMENT_AMOUNT'), $this->amount_decimals) !== 0) {
            $this->logWarning('notify data check failed, payment amount incorrect.', $params);
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(), $transaction, $params, 'fail');

            return false;
        }

        $config = $this->getConfig($transaction['t_client'], $transaction['t_issuer'], $transaction['t_xref_pa_id']);

        if (!$this->validateSignature($config, $params)) {
            $this->logWarning('notify data check failed, signature verification failed.', $params);
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(), $transaction, $params, 'fail');

            return false;
        }

        $can_confirm = (filled(array_get($params, 'LMI_SYS_PAYMENT_ID')) && filled(array_get($params, 'LMI_SYS_PAYMENT_DATE')));
        if (!$can_confirm) {
            $this->logWarning('notify data check failed. ', $params);
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(), $transaction, $params, 'fail');

            return false;
        }

        $confirm_params = [
            'gateway' => $this->getPaymentGatewayName(),
            'amount' => $transaction['t_amount'],
            'currency' => $transaction['t_currency'],
            'transaction_id' => $transaction['t_transaction_id'],
            'gateway_transaction_id' => array_get($params, 'LMI_SYS_PAYMENT_ID'),
        ];
        $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(), $transaction, $params, 'success');
        $response = $this->paymentService->confirm($transaction, $confirm_params);

        return array_get($response, 'is_success') == 'ok' ? true : false;
    }

    public function return($params)
    {
        $order_id = array_get($params, 'LMI_PAYMENT_NO');
        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $order_id, 't_tech_deleted' => false]);
        if (blank($transaction)) {
            $this->logWarning('return data check failed, transaction not found.', $params);

            return [
                'status' => 'fail',
                'message' => 'Transaction ERROR: transaction not found.',
            ];
        }
        $this->paymentService->saveTransactionLog($order_id, $params, $this->getPaymentGatewayName());

        $is_pay_done = ($transaction['t_status'] == 'done' && array_get($transaction, 't_gateway_transaction_id') == array_get($params, 'LMI_SYS_PAYMENT_ID'));

        return [
            'is_success' => $is_pay_done ? 'ok' : 'error',
            'orderid' => $transaction['t_transaction_id'],
            'issuer' => $transaction['t_issuer'],
            'amount' => $this->amountFormat($transaction['t_amount']),
            'message' => $is_pay_done ? '' : 'Transaction ERROR: Pay Failure.',
            'href' => $transaction['t_redirect_url'],
        ];
    }

    protected function initialNotify($params)
    {
        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => array_get($params, 'LMI_PAYMENT_NO'), 't_tech_deleted' => false]);

        if (blank($transaction)) {
            $this->logWarning('initial notify data check failed, transaction not found.', $params);

            return false;
        }

        $paysoft_config = $this->getConfig($transaction['t_client'], $transaction['t_issuer'], $transaction['t_xref_pa_id']);

        if ($transaction['t_status'] != 'pending') {
            $this->logWarning('initial notify data check failed, incorrect order status.', $params);

            return false;
        }

        if (array_get($params, 'LMI_MERCHANT_ID') != array_get($paysoft_config, 'current.merchant_id')) {
            $this->logWarning('initial notify data check failed, signature verification failed.', $params);

            return false;
        }

        if (bccomp($this->amountFormat($transaction['t_amount']), array_get($params, 'LMI_PAYMENT_AMOUNT'), $this->amount_decimals) !== 0) {
            $this->logWarning('notify data check failed, payment amount incorrect.', $params);

            return false;
        }

        return 'YES';
    }

    protected function getConfig($client, $issuer, $pa_id)
    {
        $app_env = $this->isSandBox();
        $config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $pa_id);
        $is_live = $config['common']['env'] == 'live' ? true : false;
        if (!$this->gatewayService->getClientUseFile()) {
            $config['current'] = $config['config'];
        } elseif ($is_live && !$app_env) {
            // Live account
            $config['current'] = $config['production'];
        } else {
            // Test account
            $config['current'] = $config['sandbox'];
        }

        return $config;
    }

    protected function validateSignature($config, $params)
    {
        $array = [
            array_get($params, 'LMI_MERCHANT_ID'),
            array_get($params, 'LMI_PAYMENT_NO'),
            array_get($params, 'LMI_SYS_PAYMENT_ID'),
            array_get($params, 'LMI_SYS_PAYMENT_DATE'),
            array_get($params, 'LMI_PAYMENT_AMOUNT'),
            array_get($params, 'LMI_PAID_AMOUNT'),
            array_get($params, 'LMI_PAYMENT_SYSTEM'),
            array_get($params, 'LMI_MODE'),
        ];

        return array_get($params, 'LMI_HASH') == strtoupper($this->generateSignature($config, $array));
    }

    protected function generateSignature(array $config, array $array)
    {
        return hash(array_get($config, 'current.signature_algorithm'), implode('', $array) . array_get($config, 'current.signature_secret_key'));
    }

    protected function amountFormat($amount)
    {
        return number_format($amount, $this->amount_decimals, '.', '');
    }

    protected function logWarning($message, $params)
    {
        Log::warning('ONLINE PAYMENT, ' . $this->getPaymentGatewayName() . ' ' . $message);
        Log::warning(json_encode($params, JSON_UNESCAPED_UNICODE));
    }
}
