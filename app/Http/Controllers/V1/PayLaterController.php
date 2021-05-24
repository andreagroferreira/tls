<?php

namespace App\Http\Controllers\V1;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use Illuminate\Http\Request;

class PayLaterController extends BaseController
{
    protected $paymentGateway;

    public function __construct(
        PaymentGatewayInterface $paymentGateway) {
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/pay_later/redirto",
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
            if ($status == 'ok') {
                return $this->sendResponse($result, 200);
            } else if ($message == 'transaction_id_not_exists') {
                return $this->sendError('P0011', 'transaction id does not exists', 400);
            } else if ($message == 'pay_later_has_beeen_choosen') {
                return $this->sendError('P0020', 'Pay onsite has been choose, You can come to our office, and pay your fee.', 400);
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
     * @OA\Get (
     *     path="/api/v1/pay_later/return",
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
}
