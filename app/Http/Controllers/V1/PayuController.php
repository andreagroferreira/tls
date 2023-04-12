<?php

namespace App\Http\Controllers\V1;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use Illuminate\Http\Request;

class PayuController extends BaseController
{
    private $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway) {
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payu/redirto",
     *     tags={"Payment API"},
     *     description="return reqeust from payu",
     *     @OA\Parameter(
     *          name="t_id",
     *          in="query",
     *          description="transaction id",
     *          required=true,
     *          @OA\Schema(type="integer", example="10000"),
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
        $params = $request->post();
        try {
            $result = $this->paymentGateway->redirto($params);
            if (!empty($result['status']) && $result['status'] == 'fail') {
                return $this->sendError('P0023', 'payu error:' . $result['message'], 400);
            }
            return $this->sendResponse($result, 200);
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payu/return",
     *     tags={"Payment API"},
     *     description="return reqeust from payu",
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
            if (!empty($init_data['status']) && $init_data['status'] == 'fail') {
                return $this->sendError('P0023', ['message' => $init_data['message'], 'href' => array_get($init_data, 'href')], 400);
            }
            return $this->sendResponse($init_data, 200);
        } catch (\Exception $e) {
            return $this->sendError('P0006', ['message' => $e->getMessage()], 400);
        }
    }
}
