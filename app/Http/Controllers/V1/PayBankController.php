<?php

namespace App\Http\Controllers\V1;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use Illuminate\Http\Request;

class PayBankController extends BaseController
{
    protected $paymentGateway;

    public function __construct(
        PaymentGatewayInterface $paymentGateway) {
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pay_bank/redirto",
     *     tags={"Payment API"},
     *     description="return reqeust from paygate",
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
        $params = $request->post();
        try {
            $result = $this->paymentGateway->redirto($params);
            $status = $result['status'] ?? '';
            $message = $result['msg'] ?? '';
            if (empty($status) && !empty($result['form_fields'])) {
                return $this->sendResponse($result, 200);
            } else if ($message == 'transaction_id_not_exists') {
                return $this->sendError('P0011', 'transaction id does not exists', 400);
            } else if ($message == 'pay_bank_has_been_chosen') {
                return $this->sendError('P0024', 'Bank Payment has been chosen, You can come to the bank, and pay your fee.', 400);
            } else if ($message == 'transaction_done_by_other_gateway') {
                return $this->sendError('P0018', 'Your transaction has been finish by another gateway, please check', 400);
            } else {
                return $this->sendError('P0006', 'unknown_error', 400);
            }
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post (
     *     path="/api/v1/pay_bank/return",
     *     tags={"Payment API"},
     *     description="return reqeust from paygate",
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
        $params = $request->post();
        try {
            $result = $this->paymentGateway->return($params);
            return $this->sendResponse($result, 200);
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pay_bank/notify",
     *     tags={"Payment API"},
     *     description="return reqeust from paygate",
     *      @OA\Parameter(
     *           name="transaction",
     *           in="query",
     *           description="transaction id",
     *           required=true,
     *           @OA\Schema(type="integer", example="10000"),
     *       ),
     *      @OA\Parameter(
     *           name="currency",
     *           in="query",
     *           description="3-digit currency code",
     *           required=true,
     *           @OA\Schema(type="string", example="RMB"),
     *       ),
     *      @OA\Parameter(
     *           name="amount",
     *           in="query",
     *           description="the amount of bank payment paid",
     *           required=true,
     *           @OA\Schema(type="float", example="10.00"),
     *       ),
     *      @OA\Parameter(
     *           name="token",
     *           in="query",
     *           description="the amount of bank payment paid",
     *           required=true,
     *           @OA\Schema(type="string", example="afdba9a05eb64b07103a340a6e20d7e5d22b2e1dc9b27dc0783f99e8114d154b"),
     *       ),
     *      @OA\Response(
     *          response="200",
     *          description="transaction confirmed",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function notify(Request $request) {
        $params = $request->post();
        try {
            $result = $this->paymentGateway->notify($params);
            $status = $result['status'] ?? '';
            $message = $result['message'] ?? '';
            if ($status == 'success') {
                return $this->sendResponse('', '200');
            } else {
                // bank payment notify error
                return $this->sendError('P0025', 'Bank payment error:' . json_encode($message), 400);
            }
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }
}
