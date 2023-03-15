<?php

namespace App\PaymentGateway;

use App\Services\ApiService;
use App\Services\CurrencyCodeService;
use App\Services\FormGroupService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionItemsService;
use App\Services\TransactionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Contracts\PaymentGateway\PaymentGatewayInterface;

class BnpPaymentGateway implements PaymentGatewayInterface
{
    protected $transactionService;
    protected $transactionItemsService;
    protected $gatewayService;
    protected $paymentService;
    protected $formGroupService;
    protected $currencyCodeService;
    protected $apiService;

    public function __construct(
        TransactionService  $transactionService,
        TransactionItemsService $transactionItemsService,
        GatewayService      $gatewayService,
        PaymentService      $paymentService,
        FormGroupService    $formGroupService,
        CurrencyCodeService $currencyCodeService,
        ApiService          $apiService
    )
    {
        $this->transactionService = $transactionService;
        $this->transactionItemsService = $transactionItemsService;
        $this->gatewayService = $gatewayService;
        $this->paymentService = $paymentService;
        $this->formGroupService = $formGroupService;
        $this->currencyCodeService = $currencyCodeService;
        $this->apiService = $apiService;
    }

    public function getPaymentGatewayName()
    {
        return 'bnp';
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
        $pa_id = $params['pa_id'] ?? '';
        $transactionData = $this->transactionService->getTransaction($t_id);
        if (blank($transactionData)) {
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: transaction not found'
            ];
        } else if ($pa_id) {
            $this->transactionService->updateById($t_id, ['t_xref_pa_id' => $pa_id]);
        }

        $issuer = $transactionData['t_issuer'];
        $oldTransactionId = $transactionData['t_transaction_id'];
        $newTransactionId = substr($issuer, 0, 2) . str_pad($t_id, 8, '0', STR_PAD_LEFT);
        $this->transactionService->updateById($t_id, ['t_transaction_id' => $newTransactionId]);
        $this->transactionItemsService->update($oldTransactionId, ['ti_xref_transaction_id' => $newTransactionId]);

        $bnp_config = $this->getConfig($transactionData['t_client'], $issuer, $pa_id);

        $get_data = [
            'userName' => array_get($bnp_config, 'current.user_name'),
            'password' => array_get($bnp_config, 'current.password'),
            'orderNumber' => $newTransactionId,
            'amount' => $this->amountFormat($transactionData['t_amount'], array_get($bnp_config, 'common.min_fraction_digits')),
            'currency' => $this->currencyCodeService->getCurrencyCode(array_get($bnp_config, 'common.currency')),
            'returnUrl' => get_callback_url(array_get($bnp_config, 'common.return_url')),
            'language' => array_get($bnp_config, 'common.language'),
            'jsonParams' => json_encode([
                'force_terminal_id' => array_get($bnp_config, 'current.terminal_id'),
                'udf1' => $transactionData['t_xref_fg_id']
            ]),
        ];

        $this->paymentService->saveTransactionLog($newTransactionId, $get_data, $this->getPaymentGatewayName());
        $response = $this->apiService->callGeneralApi('GET', array_get($bnp_config, 'current.host') . '/payment/rest/register.do?' . http_build_query($get_data));
        $this->paymentService->saveTransactionLog($newTransactionId, $response, $this->getPaymentGatewayName());

        $payment_order_id = array_get($response, 'body.orderId');
        $payment_form_url = array_get($response, 'body.formUrl');
        if (blank($payment_order_id) || blank($payment_form_url) || array_get($response, 'body.errorCode') !== '0') {
            $this->logWarning('order registration request failed.', $response);
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: Payment error, please try again.'
            ];
        }

        $this->transactionService->updateById($t_id, ['t_gateway_transaction_id' => $payment_order_id]);

        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $transactionData);

        return [
            'form_method' => 'post',
            'form_action' => $payment_form_url,
            'form_fields' => []
        ];
    }

    public function notify($params)
    {
        return true;
    }

    public function return($params)
    {
        $payment_order_id = array_get($params, 'orderId');

        $transaction = $this->transactionService->fetchTransaction(['t_gateway_transaction_id' => $payment_order_id, 't_tech_deleted' => false]);
        if (blank($transaction)) {
            $this->logWarning('return data check failed, transaction not found.', $params);
            return [
                'status' => 'fail',
                'message' => 'Transaction ERROR: transaction not found.'
            ];
        }

        $this->paymentService->saveTransactionLog($transaction['t_transaction_id'], $params, $this->getPaymentGatewayName());

        $bnp_config = $this->getConfig($transaction['t_client'], $transaction['t_issuer'], $transaction['t_xref_pa_id']);
        $get_data = [
            'userName' => array_get($bnp_config, 'current.user_name'),
            'password' => array_get($bnp_config, 'current.password'),
            'orderId' => $payment_order_id,
            'language' => array_get($bnp_config, 'common.language'),
        ];

        $response = $this->apiService->callGeneralApi('GET', array_get($bnp_config, 'current.host') . '/payment/rest/confirmOrder.do?' . http_build_query($get_data));
        $this->paymentService->saveTransactionLog($transaction['t_transaction_id'], $response, $this->getPaymentGatewayName());

        if (!(
            array_get($response, 'body.actionCode') === 0
            && array_get($response, 'body.params.respCode') === '00'
            && array_get($response, 'body.OrderStatus') == 2
        )) {
            $this->logWarning('payment api confirm failed.', $response);
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $response,'fail');
            return [
                'status' => 'fail',
                'orderid' => array_get($transaction, 't_transaction_id'),
                'message' => array_get($response, 'body.params.respCode_desc', array_get($response, 'body.actionCodeDescription', 'Payment failed.'))
            ];
        }

        $received_amount = $this->amountFormat(array_get($response, 'body.Amount'), array_get($bnp_config, 'common.min_fraction_digits'), true);
        $received_currency = $this->currencyCodeService->getCurrency(array_get($response, 'body.currency'));
        $res = $this->paymentService->confirm($transaction, [
            'gateway' => $this->getPaymentGatewayName(),
            'amount' => $received_amount,
            'currency' => $received_currency,
            'transaction_id' => $transaction['t_transaction_id'],
            'gateway_transaction_id' => $payment_order_id,
        ]);
        $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $response,'success');
        return array_merge($res, ['payment_data' => [
            'now' => Carbon::now()->format('Y-m-d H:i'),
            'response_code' => array_get($response, 'body.params.respCode_desc'),
            'order_id' => $payment_order_id,
            'order_number' => $transaction['t_transaction_id'],
            'approval_code' => array_get($response, 'body.approvalCode'),
            'amount' => $received_amount . ' ' . $received_currency,
        ]]);
    }

    protected function getConfig($client, $issuer, $pa_id)
    {
        $app_env = $this->isSandBox();
        $config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $pa_id);
        $is_live = $config['common']['env'] == 'live' ? true : false;
        if (!$this->gatewayService->getClientUseFile()) {
            $config['current'] = $config['config'];
        } else if ($is_live && !$app_env) {
            // Live account
            $config['current'] = $config['production'];
        } else {
            // Test account
            $config['current'] = $config['sandbox'];
        }

        return $config;
    }

    protected function amountFormat($amount, $amount_decimals, $is_return_params = false)
    {
        if ($amount_decimals) {
            while ($amount_decimals > 0) {
                if ($is_return_params) {
                    $amount /= 10;
                } else {
                    $amount *= 10;
                }
                $amount_decimals--;
            }
        }

        return $amount;
    }

    protected function logWarning($message, $params)
    {
        Log::warning('ONLINE PAYMENT, ' . $this->getPaymentGatewayName() . ' ' . $message);
        Log::warning(json_encode($params, JSON_UNESCAPED_UNICODE));
    }
}
