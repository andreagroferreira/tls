<?php

namespace App\Http\Controllers\V1;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\GatewayService;
use Illuminate\Http\Request;

class KBankController extends BaseController
{
    private $paymentGateway;
    private $gatewayService;

    public function __construct(PaymentGatewayInterface $paymentGateway, GatewayService $gatewayService) {
        $this->paymentGateway = $paymentGateway;
        $this->gatewayService = $gatewayService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/k-bank/redirto",
     *     tags={"Payment API"},
     *     description="return reqeust from k-bank",
     *     @OA\Parameter(
     *          name="t_id",
     *          in="query",
     *          description="transaction id",
     *          required=true,
     *          @OA\Schema(type="integer", example="10000"),
     *      ),
     *     @OA\Parameter(
     *          name="token",
     *          in="query",
     *          description="token id",
     *          required=true,
     *          @OA\Schema(type="integer", example="tokn_test_2088099ffb71c1a4a3441419cd20fec81d7e7"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="transaction created",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function redirto(Request $request) {
        $params = $request->only(['t_id', 'token', 'pa_id']);
        $validator = validator($params, [
            't_id'  => 'required|int',
            'token' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->sendError('P0006', $validator->errors()->first(), 400);
        }
        try {
            $result = $this->paymentGateway->redirto($params);
            return $this->sendResponse($result, 200);
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }

    }

    /**
     * @OA\Post(
     *     path="/api/v1/k-bank/notify",
     *     tags={"Payment API"},
     *     description="return reqeust from k-bank",
     *      @OA\Response(
     *          response="200",
     *          description="transaction created",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function notify(Request $request) {
        $return_params = $request->post();
        if (empty($return_params)) {
            $this->sendError('P0009', "no_data_received", 400);
        }

        try {
            $result = $this->paymentGateway->notify($return_params);
            return $this->sendResponse($result, 200);
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }

    }

    /**
     * @OA\Post(
     *     path="/api/v1/k-bank/return",
     *     tags={"Payment API"},
     *     description="return reqeust from k-bank",
     *      @OA\Response(
     *          response="200",
     *          description="transaction created",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function return(Request $request) {
        $return_params = $request->post();
        if (empty($return_params)) {
            $this->sendError('P0009', ['message' => "no_data_received"], 400);
        }
        try {
            $init_data = $this->paymentGateway->return($return_params);
            $status = $init_data['is_success'] ?? '';
            $message = $init_data['message'] ?? '';
            if ($status == 'ok') {
                return $this->sendResponse($init_data, 200);
            } else {
                // paggate error
                return $this->sendError('P0019', ['message' => 'k-bank error:' . $message, 'href' => array_get($init_data, 'href')], 400);
            }
        } catch (\Exception $e) {
            return $this->sendError('P0006', ['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/k-bank/config",
     *     tags={"Payment API"},
     *     description="return config for k-bank",
     *      @OA\Parameter(
     *          name="client",
     *          in="query",
     *          description="define which client you want to create the acccount in",
     *          required=true,
     *          @OA\Schema(type="string", format="email", example="be"),
     *      ),
     *      @OA\Parameter(
     *          name="issuer",
     *          in="query",
     *          description="the issuer in database",
     *          required=true,
     *          @OA\Schema(type="string", format="email", example="thBKK2be"),
     *      ),
     *      @OA\Parameter(
     *          name="payment",
     *          in="query",
     *          description="the payment name",
     *          required=true,
     *          @OA\Schema(type="string", format="email", example="k-bank"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="transaction created",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function fetchConfig(Request $request) {
        $params = $request->post();

        $validator = validator($params, [
            'client'    => 'required|string',
            'issuer'    => 'required|string',
            'payment'   => 'required|string',
            'pa_id'     => 'required|int',
        ]);

        if ($validator->fails()) {
            $this->sendError('P0009', "client or issuer, payment no_data_received", 400);
        }
        try {
            $kbank_config = $this->gatewayService->getKbankConfig($params['client'], $params['issuer'], $params['payment'], $params['pa_id']);
            if (!empty($kbank_config)) {
                return $this->sendResponse($kbank_config, 200);
            } else {
                return $this->sendError('P0019', 'Payment config does not exist', 400);
            }
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }
}
