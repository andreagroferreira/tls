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
            } else {
                if ($message == 'transaction_id_not_exists') {
                    $error_code = 'P0011';
                    $error_msg = 'transaction id does not exists';
                } else if ($message == 'pay_bank_has_been_chosen') {
                    $error_code = 'P0024';
                    $error_msg = 'Bank Payment has been chosen, You can come to the bank, and pay your fee.';
                } else if ($message == 'transaction_done_by_other_gateway') {
                    $error_code = 'P0018';
                    $error_msg = 'Your transaction has been finish by another gateway, please check';
                } else {
                    $error_code = 'P0006';
                    $error_msg = 'unknown_error';
                }
                return $this->sendError($error_code, $error_msg, 400);
            }
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }
    /**
     * @OA\Get (
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
}
