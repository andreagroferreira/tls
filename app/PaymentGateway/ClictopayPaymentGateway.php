<?php

namespace App\PaymentGateway;

use App\Services\CurrencyCodeService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionLogsService;
use App\Services\TransactionService;
use App\Services\TransactionItemsService;
use App\Services\ApiService;
use Illuminate\Support\Facades\Log;
use App\Contracts\PaymentGateway\PaymentGatewayInterface;

class ClictopayPaymentGateway implements PaymentGatewayInterface
{
    private $currencyCodeService;
    private $transactionLogsService;
    private $transactionService;
    private $transactionItemsService;
    private $gatewayService;
    private $paymentService;
    private $apiService;

    public function __construct(
        CurrencyCodeService $currencyCodeService,
        TransactionService $transactionService,
        TransactionLogsService $transactionLogsService,
        TransactionItemsService $transactionItemsService,
        GatewayService $gatewayService,
        PaymentService $paymentService,
        ApiService $apiService
    )
    {
        $this->currencyCodeService    = $currencyCodeService;
        $this->transactionService     = $transactionService;
        $this->transactionLogsService = $transactionLogsService;
        $this->transactionItemsService = $transactionItemsService;
        $this->gatewayService         = $gatewayService;
        $this->paymentService         = $paymentService;
        $this->apiService             = $apiService;
    }

    public function getPaymentGatewayName()
    {
        return 'clictopay';
    }

    public function isSandBox()
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    public function checkout()
    {

    }

    public function notify($params)
    {
        return 'notify';
    }

    public function redirto($t_id)
    {
        $translationsData = $this->transactionService->getTransaction($t_id);
        $app_env = $this->isSandBox();
        $client  = $translationsData['t_client'];
        $issuer  = $translationsData['t_issuer'];
        $orderId = $translationsData['t_transaction_id'] ?? '';
        $clictopay_config = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName());
        $is_live        = $clictopay_config['common']['env'] == 'live' ? true : false;
        if ($is_live && !$app_env) {
            // Live account
            $init_hosturl = $clictopay_config['prod']['host'];
            $user_name    = $clictopay_config['prod']['user_name'];
            $password     = $clictopay_config['prod']['password'];
        } else {
            // Test account
            $init_hosturl = $clictopay_config['sandbox']['sandbox_host'];
            $user_name    = $clictopay_config['sandbox']['sandbox_user_name'];
            $password     = $clictopay_config['sandbox']['sandbox_password'];
        }
        $currency   = $translationsData['t_currency'] ?? $clictopay_config['common']['currency'];
        $return_url = get_callback_url($clictopay_config['common']['return_url']);
        $params = array(
            'userName'       => $user_name,
            'password'       => $password,
            'orderNumber'    => $orderId,
            'amount'         => $translationsData['t_amount'] * 1000,
            'currency'       => $this->currencyCodeService->getCurrencyCode($currency),
            'returnUrl'      => $return_url,
            'language'       => 'en',
            'pageView'       => 'DESKTOP'
        );
        $init_host_url = $init_hosturl . '/register.do';
        $response = $this->apiService->callGeneralApi('post', $init_host_url, $params);
        if ($response['status'] != 200 || (!empty($response['body']) && isset($response['body']['errorCode']))) {
            return ['status' => 'fail', 'content' => json_encode($response)];
        }
        $responseData = $response['body'];
        if (!empty($responseData['orderId'])) {
            $this->transactionService->update(['t_transaction_id' => $orderId], ['t_gateway_transaction_id' => $responseData['orderId'], 't_gateway' => $this->getPaymentGatewayName()]);
        }
        $payment_page_url = $responseData['formUrl'];
        return [
            'form_method' => 'get',
            'form_action' => $payment_page_url,
            'form_fields' => ['mdOrder' => $responseData['orderId']]
        ];
    }

    public function return($return_params)
    {
        $app_env    = $this->isSandBox();
        $language   = $return_params['lang'];
        $gateway_id = $return_params['orderId'];
        $transaction = $this->transactionService->fetchTransaction(['t_gateway_transaction_id' => $gateway_id, 't_tech_deleted' => false]);
        if (empty($transaction)) {
            Log::warning("ONLINE PAYMENT, clictopay : No transaction found in the database for " . $gateway_id . "\n" .
                json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return [
                'status'  => 'error',
                'message' => 'Transaction ERROR: transaction not found'
            ];
        }
        $clictopay_config = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName());
        $is_live          = $clictopay_config['common']['env'] == 'live' ? true : false;
        if ($is_live && !$app_env) {
            // Live account
            $init_hosturl = $clictopay_config['prod']['host'];
            $user_name    = $clictopay_config['prod']['user_name'];
            $password     = $clictopay_config['prod']['password'];
        } else {
            // Test account
            $init_hosturl = $clictopay_config['sandbox']['sandbox_host'];
            $user_name    = $clictopay_config['sandbox']['sandbox_user_name'];
            $password     = $clictopay_config['sandbox']['sandbox_password'];
        }
        $init_host_url = $init_hosturl . '/getOrderStatusExtended.do?orderId=' . $gateway_id . '&lang=' . $language . '&userName=' . $user_name . '&password=' . $password;
        $response = $this->apiService->callGeneralApi('get', $init_host_url);
        if ($response['status'] != 200 || (isset($response['body']['errorMessage']) && $response['body']['errorMessage'] != 'Success')) {
            return ['status' => 'fail', 'content' => json_encode($response)];
        }
        $responseData   = $response['body'];
        $order_id       = $responseData['orderNumber'];
        $order_status   = $responseData['orderStatus'];
        $order_amount   = $responseData['amount'] / 1000;
        $order_currency = $this->currencyCodeService->getCurrency($responseData['currency']);
        switch ($order_status) {
            case 0:
                $message = "order recorded, but not paid.";
                break;
            case 1:
                $message = "pre-authorization amount blocked (for payment in two stages).";
                break;
            case 2:
                $message = "The amount was deposited successfully.";
                break;
            case 3:
                $message = "license cancellation.";
                break;
            case 4:
                $message = "refunded transaction.";
                break;
            default;
        }
        if ($responseData['errorMessage'] == 'Success' & $order_status == 2) {
            $confirm_params = [
                'gateway'                => $this->getPaymentGatewayName(),
                'amount'                 => $order_amount,
                'currency'               => $order_currency,
                'transaction_id'         => $transaction['t_transaction_id'],
                'gateway_transaction_id' => $order_id,
            ];
            return $this->paymentService->confirm($transaction, $confirm_params);
        } else {
            return array(
                'is_success' => 'error',
                'orderid'    => $transaction['t_transaction_id'],
                'issuer'     => $transaction['t_issuer'],
                'amount'     => $order_amount,
                'message'    => $message,
                'href'       => $transaction['t_redirect_url']
            );
        }
    }
}
