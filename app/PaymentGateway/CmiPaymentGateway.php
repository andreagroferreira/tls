<?php

namespace App\PaymentGateway;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\ApiService;
use App\Services\GatewayService;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;

class CmiPaymentGateway implements PaymentGatewayInterface
{
    private $transactionService;
    private $gatewayService;
    private $paymentService;
    private $apiService;

    public function __construct(
        TransactionService $transactionService,
        GatewayService $gatewayService,
        PaymentService $paymentService,
        ApiService $apiService
    )
    {
        $this->transactionService = $transactionService;
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
            return json_encode([
                'code' => 400,
                'msg'  => 'Illegal parameter',
            ]);
            exit;
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
            return 'APPROVED';
            exit;
        }

        $config     = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName());
        $cmi_config = array_merge($config['common'], $this->isSandBox() ? $config['sandbox'] : $config['prod']);
        $isValid    = $this->validate($cmi_config['storeKey'] ?? [], $params);

        if (!$isValid) {
            return 'APPROVED';
            exit;
        }

        $response = $this->paymentService->confirm($transaction, $confirm_params);
        if ($response['status'] != 'ok') {
            exit;
        }

        if (($params['Response'] == 'Approved') && ($params['ProcReturnCode'] == '00')) {
            return "ACTION=POSTAUTH";
        } else {
            Log::warning("ONLINE PAYMENT, CMI: Payment authorization check failed : " . json_encode($_POST, JSON_UNESCAPED_UNICODE));
            return "APPROVED";
        }

    }

    public function redirto($t_id)
    {
        $transaction = $this->transactionService->getTransaction($t_id);
        if (empty($transaction)) {
            return 'error';
        }
        $client      = $transaction['t_client'];
        $issuer      = $transaction['t_issuer'];
        $fg_id       = $transaction['t_xref_fg_id'];
        $config      = $this->gatewayService->getGateway($client, $issuer, $this->getPaymentGatewayName());
        $cmi_config  = array_merge($config['common'], $this->isSandbox() ? $config['sandbox'] : $config['prod']);
        $application = $this->apiService->callTlsApi('GET', '/tls/v2/' . $client . '/form_group/' . $fg_id);
        $u_email     = $application['body']['u_relative_email'] ?? $application['body']['u_email'] ?? "tlspay-{$client}-{$fg_id}@tlscontact.com";
        $params      = [
            'clientid'      => $cmi_config['merchant_id'],
            'storetype'     => $cmi_config['storetype'],
            'TranType'      => $cmi_config['tranType'],
            'amount'        => $transaction['t_amount'],
            'currency'      => $transaction['t_currency'],
            'oid'           => $transaction['t_transaction_id'],
            'okUrl'         => url($cmi_config['okUrl']),
            'failUrl'       => $transaction['t_redirect_url'],
            'lang'          => 'fr',
            'email'         => $u_email,
            'rnd'           => microtime(),
            'hashAlgorithm' => $cmi_config['hashAlgorithm'],
            'shopurl'       => $transaction['t_redirect_url'] . $t_id,
            'callbackUrl'   => url($cmi_config['callbackUrl']),
        ];

        $params['hash']     = $this->getHash($cmi_config['storeKey'], $params);
        $params['encoding'] = 'UTF-8';

        $params['trantype']    = $params['TranType'];
        $params['CallbackURL'] = $params['callbackUrl'];
        unset($params['TranType']);
        unset($params['callbackUrl']);

        $string = "";
        foreach ($params as $name => $value) {
            $string .= "<input type='hidden' name='$name' value='$value' readonly>";
        }
        return [
            'status'  => 'success',
            'content' => "
                <div style='display:none' >
                    <form id='payment_form' action='{$cmi_config['host']}' method='post'>" . $string . "</form>
                </div>"
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

        $config     = $this->gatewayService->getGateway($transaction['t_client'], $transaction['t_issuer'], $this->getPaymentGatewayName());
        $cmi_config = array_merge($config['common'], $this->isSandbox() ? $config['sandbox'] : $config['prod']);
        $isValid    = $this->validate($cmi_config['storeKey'] ?? '', $params);
        if (!$isValid) {
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

}
