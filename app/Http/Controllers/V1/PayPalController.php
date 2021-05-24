<?php


namespace App\Http\Controllers\V1;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayPalController extends BaseController
{
    private $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway) {
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/paypal/redirto",
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
        $orderId = $request->post('t_id');
        try {
            $result = $this->paymentGateway->redirto($orderId);
            return $this->sendResponse($result, 200);
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/paypal/notify",
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
    public function notify(Request $request) {
        $params = [
            'formData' => $request->post(),
            'urlData' => $request->input()
        ];
        try {
            $result = $this->paymentGateway->notify($params);
            $status = $result['status'] ?? '';
            $message = $result['message'] ?? '';
            if ($status == 'success') {
                return $this->sendResponse('', '200');
            } else {
                // paypal notify error
                return $this->sendError('P0021', 'paypal error:' . json_encode($message), 400);
            }
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/paypal/return",
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
        $params = $request->get('t_id');
        try {
            $result = $this->paymentGateway->return($params);
            $status = $result['is_success'] ?? '';
            if ($status == 'ok') {
                return $this->sendResponse($result, 200);
            } else {
                // The deal was not completed or delay
                return $this->sendError('P0022', $result, 400);
            }
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }

}
