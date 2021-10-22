<?php

namespace App\Http\Controllers\V1;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use Illuminate\Http\Request;

class ClictopayController extends BaseController
{
    private $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway) {
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/clictopay/redirto",
     *     tags={"Payment API"},
     *     description="return reqeust from clictopay",
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
        $t_id = $request->post('t_id');
        try {
            $result = $this->paymentGateway->redirto($t_id);
            if (!empty($result['status']) && $result['status'] == 'fail') {
                return $this->sendError('P0023', 'clictopay error:' . $result['content'], 400);
            } else {
                return $this->sendResponse($result, 200);
            }
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/clictopay/return",
     *     tags={"Payment API"},
     *     description="return reqeust from clictopay",
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
            $this->sendError('P0009', "no_data_received", 400);
        }
        try {
            $init_data = $this->paymentGateway->return($return_params);
            return $this->sendResponse($init_data, 200);
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }
}
