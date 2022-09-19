<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\ApiService;
use App\Services\FormGroupService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;

class CmiPaymentGateway implements PaymentGatewayInterface
{
    private $transactionService;
    private $formGroupService;
    private $gatewayService;
    private $paymentService;
    private $apiService;

    public function __construct(
        TransactionService $transactionService,
        FormGroupService $formGroupService,
        GatewayService $gatewayService,
        PaymentService $paymentService,
        ApiService $apiService
    )
    {
        $this->transactionService = $transactionService;
        $this->formGroupService   = $formGroupService;
        $this->gatewayService     = $gatewayService;
        $this->paymentService     = $paymentService;
        $this->apiService         = $apiService;
    }

    public function isSandBox(): bool
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    public function getPaymentGatewayName()
    {
        return 'cmi';
    }

    public function checkout()
    {
        return true;
    }

    public function notify($params)
    {
        $transaction_id = $params['oid'] ?? '';
        $this->paymentService->saveTransactionLog($transaction_id, $params, $this->getPaymentGatewayName());

        if (empty($transaction_id)) {
            return [
                'status'  => 'error',
                'message' => 'Illegal parameter'
            ];
        }
        $confirm_params = [
            'gateway'                => $this->getPaymentGatewayName(),
            'amount'                 => $params['amount'],
            'currency'               => $params['currency'],
            'transaction_id'         => $params['oid'],
            'gateway_transaction_id' => $params['TransId'],
        ];
        $transaction    = $this->transactionService->fetchTransaction(['t_transaction_id' => $transaction_id]);
        if (empty($transaction)) {
            return [
                'status'  => 'error',
                'message' => 'APPROVED'
            ];
        }

        $config     = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName(), $transaction['t_xref_pa_id']);
        $cmi_config = array_merge($config['common'], $this->getPaySecret($config));
        $isValid    = $this->validate($cmi_config['storeKey'] ?? [], $params);

        if (!$isValid) {
            return [
                'status'  => 'error',
                'message' => 'APPROVED'
            ];
        }
        $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'success');

        if (($params['Response'] == 'Approved') && ($params['ProcReturnCode'] == '00')) {
            $response = $this->paymentService->confirm($transaction, $confirm_params);
            if ($response['is_success'] != 'ok') {
                return [
                    'status'  => 'error',
                    'message' => $response['message']
                ];
            }
            return "ACTION=POSTAUTH";
        } else {
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'fail');
            Log::warning("ONLINE PAYMENT, CMI: Payment authorization check failed : " . json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return [
                'status'  => 'error',
                'message' => 'APPROVED'
            ];
        }

    }

    public function redirto($params)
    {
        $t_id = $params['t_id'];
        $pa_id = $params['pa_id'] ?? null;
        $transaction = $this->transactionService->getTransaction($t_id);
        if (blank($transaction)) {
            return [
                'status' => 'error',
                'message' => 'Transaction ERROR: transaction not found'
            ];
        } else if ($pa_id) {
            $this->transactionService->updateById($t_id, ['t_xref_pa_id' => $pa_id]);
        }
        $client      = $transaction['t_client'];
        $issuer      = $transaction['t_issuer'];
        $fg_id       = $transaction['t_xref_fg_id'];

        $config      = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName(), $pa_id);
        $cmi_config  = array_merge($config['common'], $this->getPaySecret($config));

        $application = $this->formGroupService->fetch($fg_id, $client);
        $form_list   = $this->formGroupService->fetchFomrs($fg_id, $client);
        $u_email     = $application['u_relative_email'] ?? $application['u_email'] ?? "tlspay-{$client}-{$fg_id}@tlscontact.com";
        $params      = [
            'clientid'      => $cmi_config['merchant_id'],
            'storetype'     => $cmi_config['storetype'],
            'TranType'      => $cmi_config['tranType'],
            'amount'        => $transaction['t_amount'],
            'currency'      => $transaction['t_currency'],
            'oid'           => $transaction['t_transaction_id'],
            'okUrl'         => get_callback_url($cmi_config['okUrl']),
            'failUrl'       => get_callback_url($cmi_config['failUrl']),
            'lang'          => 'fr',
            'email'         => $u_email,
            'rnd'           => microtime(),
            'BillToName'    => !empty($form_list) ? $form_list[0]['f_pers_surnames'] . ' ' . $form_list[0]['f_pers_givennames'] : '',
            'hashAlgorithm' => $cmi_config['hashAlgorithm'],
            'shopurl'       => $transaction['t_redirect_url'] . $t_id,
            'callbackUrl'   => get_callback_url($cmi_config['callbackUrl']),
        ];

        $params['hash']     = $this->getHash($cmi_config['storeKey'], $params);
        $params['encoding'] = 'UTF-8';

        $params['trantype']    = $params['TranType'];
        $params['CallbackURL'] = $params['callbackUrl'];
        unset($params['TranType']);
        unset($params['callbackUrl']);

        $this->paymentService->PaymentTransationBeforeLog($this->getPaymentGatewayName(), $transaction);

        return [
            'form_method' => 'post',
            'form_action' => $cmi_config['host'],
            'form_fields' => $params,
        ];
    }

    public function return($params)
    {
        $transaction_id = $params['oid'] ?? '';
        $this->paymentService->saveTransactionLog($transaction_id, $params, $this->getPaymentGatewayName());

        $transaction = $this->transactionService->fetchTransaction(['t_transaction_id' => $transaction_id]);
        if (empty($transaction)) {
            Log::warning("ONLINE PAYMENT, CMI : No transaction found in the database for " . $transaction_id . "\n" .
                json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return [
                'status'  => 'error',
                'message' => 'Transaction ERROR: transaction not found'
            ];
        }
        if (isset($params['Response']) && $params['Response'] == 'Error') {
            return [
                'is_success' => 'error',
                'orderid' => $transaction['t_transaction_id'],
                'issuer' => $transaction['t_issuer'],
                'amount' => $transaction['t_amount'],
                'message' => $params['ErrMsg'],
                'href' => $transaction['t_redirect_url']
            ];
        }
        $config     = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName(), $transaction['t_xref_pa_id']);
        $cmi_config = array_merge($config['common'], $this->getPaySecret($config));
        $isValid    = $this->validate($cmi_config['storeKey'] ?? '', $params);
        if (!$isValid) {
            $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'fail');
            return [
                'status'  => 'error',
                'message' => 'Request ERROR: params validate failed'
            ];
        }

        $confirm_params = [
            'gateway'                => $this->getPaymentGatewayName(),
            'amount'                 => $params['amount'],
            'currency'               => $params['currency'],
            'transaction_id'         => $params['oid'],
            'gateway_transaction_id' => $params['TransId'],
        ];
        $this->paymentService->PaymentTransactionCallbackLog($this->getPaymentGatewayName(),$transaction, $params,'success');
        return $this->paymentService->confirm($transaction, $confirm_params);
    }

    private function validate($storeKey, $params)
    {
        //make sign
        if (empty($params)) {
            Log::warning("ONLINE PAYMENT, CMI: Did not return any data" . json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return false;
        }
        //validate sign
        if ($params['HASH'] != $this->getHash($storeKey, $params)) {
            Log::warning("ONLINE PAYMENT, CMI: digital signature check failed : " . json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return false;
        }

        return true;
    }

    private function getHash($storeKey, $params)
    {
        $params_kyes = array_keys($params);
        natcasesort($params_kyes);
        $hash_val = '';
        foreach ($params_kyes as $item) {
            $paramValue        = trim($params[$item]);
            $escapedParamValue = str_replace("|", "\\|", str_replace("\\", "\\\\", $paramValue));
            if (strtolower($item) != "hash" && strtolower($item) != "encoding") {
                $hash_val = $hash_val . $escapedParamValue . "|";
            }
        }
        $escapedStoreKey     = str_replace("|", "\\|", str_replace("\\", "\\\\", $storeKey));
        $hash_val            = $hash_val . $escapedStoreKey;
        $calculatedHashValue = hash('sha512', $hash_val);
        return base64_encode(pack('H*', $calculatedHashValue));
    }

    private function getPaySecret($pay_config) {
        if ($this->gatewayService->getClientUseFile()) {
            $key = 'config';
        } else {
            $app_env = $this->isSandBox();
            $is_live = ($pay_config['common']['env'] == 'live');
            $key = ($is_live && !$app_env) ? 'prod' : 'sandbox';
        }
        return $pay_config[$key];
    }
}
