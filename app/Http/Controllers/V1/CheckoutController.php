<?php

namespace App\Http\Controllers\V1;

use App\Services\GatewayService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CheckoutController extends BaseController
{
    private $transactionService;
    private $gatewayService;

    public function __construct(
        TransactionService $transactionService,
        GatewayService $gatewayService
    ) {
        $this->transactionService = $transactionService;
        $this->gatewayService = $gatewayService;
    }

    public function isSandBox()
    {
        return env('APP_ENV') === 'production' ? false : true;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/checkout/{t_id}",
     *     tags={"Payment API"},
     *     description="get the transaction details according to fg_id",
     *
     *      @OA\Parameter(
     *          name="t_id",
     *          in="path",
     *          description="the transaction id, t_id in transaction table",
     *          required=true,
     *
     *          @OA\Schema(type="integer", example="10000"),
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="get the transaction information",
     *
     *          @OA\JsonContent(),
     *      ),
     *
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     *      @OA\Response(
     *          response="404",
     *          description="transaction not found"
     *      ),
     * )
     */
    public function checkout(Request $request)
    {
        $lang = $request->get('lang') ?? 'en-us';
        $t_id = $request->route('t_id');

        try {
            $transaction = $this->transactionService->getTransaction($t_id);

            if (empty($transaction)) {
                // transaction not found
                return $this->sendError('P0001', '', 204);
            }
            if ($transaction['t_status'] == 'close') {
                // transaction has been cancelled
                return $this->sendError('P0002', [
                    'href' => $transaction['t_onerror_url'],
                ], 400);
            }
            if ($transaction['t_status'] == 'done') {
                // transaction already done
                return $this->sendError('P0003', [
                    'href' => $transaction['t_redirect_url'],
                ], 400);
            }
            if ($transaction['t_status'] == 'waiting') {
                // The deal was not completed or delay
                return $this->sendError('P0022', [
                    'transaction_id' => $transaction['t_transaction_id'],
                ]);
            }
            $expiration_time = strtotime(Carbon::parse($transaction['t_expiration'], $this->transactionService->getDbTimeZone())->toDateTimeString());
            $now_time = strtotime($this->transactionService->getDbNowTime()->toDateTimeString());
            if ($now_time > $expiration_time) {
                // transaction expired
                return $this->sendError('P0004', [
                    'href' => $transaction['t_onerror_url'],
                ], 400);
            }

            $client = $transaction['t_client'];
            $issuer = $transaction['t_issuer'];
            $service = $transaction['t_service'];
            $payment_gateways = $this->gatewayService->getGateways($client, $issuer, $service);
            $is_postal = $transaction['t_workflow'] == 'postal';
            if ($is_postal && empty($payment_gateways)) {
                // Payment gateway not found for postal
                return $this->sendError('P0005', [
                    'href' => $transaction['t_onerror_url'],
                ], 400);
            }

            $this->transactionService->updateLanguage($transaction, $lang);

            // pay onsite
            $is_pay_onsite = $transaction['t_gateway'] == 'pay_later';
            if ($is_pay_onsite) {
                $result = [
                    'transaction' => $transaction,
                    'lang' => $lang,
                    'redirect_url' => $transaction['t_redirect_url'],
                ];

                return $this->sendResponse($result, 200);
            }
            // forbidden to modify the gateway
            $app_env = $this->isSandBox() ? 'sandbox' : 'production';
            $selected_gateway = $transaction['t_gateway'] . '_' . $app_env;
            if ($transaction['t_gateway'] == 'pay_later') {
                $payment_gateways = [];
            } elseif (in_array($selected_gateway, array_keys($payment_gateways))) {
                $payment_gateways = [
                    $selected_gateway => $payment_gateways[$selected_gateway],
                ];
            }
            $getClientUseFile = $this->gatewayService->getClientUseFile();
            if ($getClientUseFile) {
                foreach ($payment_gateways as $key => $value) {
                    if ($key !== 'pay_later') {
                        if (!array_key_exists($app_env, $value)) {
                            unset($payment_gateways[$key]);
                        }
                    }
                    $payment_gateways_new = [];
                    foreach ($payment_gateways as $key => $value) {
                        $payment_gateways_new[$key] = $value;
                        $payment_gateways_new[$key]['pa_id'] = '';
                        $payment_gateways_new[$key]['psp_code'] = $key;
                        $payment_gateways_new[$key]['type'] = $app_env;
                    }
                    $payment_gateways = $payment_gateways_new;
                }
            }
            $left_time = $expiration_time - $now_time;
            $data = [
                'transaction' => $transaction,
                'payment_gateways' => $payment_gateways,
                'left_time' => $left_time,
                'is_postal' => $is_postal,
                'lang' => $lang,
            ];

            return $this->sendResponse($data, 200);
        } catch (\Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());

            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }
}
