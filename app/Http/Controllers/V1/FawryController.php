<?php

namespace App\Http\Controllers\V1;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use Illuminate\Http\Request;

class FawryController extends BaseController
{
    private $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway) {
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/fawry/redirto",
     *     tags={"Payment API"},
     *     description="background callback from fawry",
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
            $redirToResult = $this->paymentGateway->redirto($params);
            $status = $redirToResult['status'] ?? '';
            $message = $redirToResult['msg'] ?? '';
            if (empty($status) && !empty($redirToResult['form_fields'])) {
                return $this->sendResponse($redirToResult, 200);
            } else if ($status == 'fail') {
                if ($message == "Transaction items can`t be parsed.") {
                    return $this->sendError('P0007', $redirToResult, 400);
                } else if ($message == 'Transaction items not found.') {
                    return $this->sendError('P0008', $redirToResult, 400);
                } else if ($message == 'Payment request failed.') {
                    return $this->sendError('P0006', 'unknown_error', 400);
                }
            }
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/fawry/notify",
     *     tags={"Payment API"},
     *     description="background callback from fawry",
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
        $notify_params = $request->post();
        info('Receive notity data: ' . json_encode($notify_params, JSON_UNESCAPED_UNICODE));
        if (empty($notify_params)) {
            return $this->sendError('P0009', 'no_data_received', 400);
        }
        try {
            $notify_result = $this->paymentGateway->notify($notify_params);
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
        $notify_status = $notify_result['status'] ?? '';
        $notify_message = $notify_result['message'] ?? '';
        if ($notify_status == 'success') {
            return $this->sendEmptyResponse();
        } else if ($notify_message == 'empty_merchant_ref_number') {
            return $this->sendError('P0010', 'merchantRefNumber is empty', 400);
        } else if ($notify_message == 'transaction_id_not_exists') {
            return $this->sendError('P0011', 'transaction id does not exists', 400);
        } else if ($notify_message == 'transaction_cancelled') {
            return $this->sendError('P0012', 'transaction has been cancelled', 400);
        } else if ($notify_message == 'signature_verification_failed') {
            return $this->sendError('P0013', 'signature verification failed', 400);
        } else if ($notify_message == 'payment_amount_incorrect') {
            return $this->sendError('P0014', 'payment amount is incorrect', 400);
        } else {
            return $this->sendError('P0006', 'unknown_error', 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/fawry/return",
     *     tags={"Payment API"},
     *     description="return reqeust from fawry",
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
            return $this->sendError('P0009', 'no_data_received', 400);
        }
        try {
            $init_data = $this->paymentGateway->return($return_params);
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
        $status = $init_data['is_success'] ?? '';
        $message = $init_data['message'] ?? '';
        if ($status == 'ok') {
            return $this->sendResponse($init_data, 200);
        } else if ($message == 'empty_charge_response_fawry') {
            return $this->sendError('P0015', 'empty charge response from fawry', 400);
        } else if ($message == 'empty_merchant_ref_number') {
            return $this->sendError('P0010', 'merchantRefNumber is empty', 400);
        } else if ($message == 'transaction_id_not_exists') {
            return $this->sendError('P0011', 'transaction id does not exists', 400);
        } else if ($message == 'payment_amount_incorrect') {
            return $this->sendError('P0014', 'payment amount is incorrect', 400);
        } else if ($message == 'transaction_has_been_paid_already') {
            return $this->sendError('P0017', 'transaction has been paid already', 400);
        } else if ($message == 'unknown_error') {
            return $this->sendError('P0006', 'unknown_error', 400);
        } else {
            // other error from fawry
            return $this->sendError('P0006', 'unknown_error: ' . $message, 400);
        }
    }
}
