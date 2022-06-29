<?php

namespace App\Http\Controllers\V1;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use Illuminate\Http\Request;

class BnpController extends BaseController
{
    private $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/bnp/redirto",
     *     tags={"Payment API"},
     *     description="return reqeust from bnp",
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
    public function redirto(Request $request)
    {
        $t_id = $request->post('t_id');
        try {
            $result = $this->paymentGateway->redirto($t_id);
            if (array_get($result, 'status') == 'error') {
                return $this->sendError('P0001', array_get($result, 'message'));
            }

            return $this->sendResponse($result);
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/bnp/return",
     *     tags={"Payment API"},
     *     description="return reqeust from bnp",
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
    public function return(Request $request)
    {
        $return_params = $request->post();
        if (empty($return_params)) {
            $this->sendError('P0009', ['message' => "no_data_received"], 400);
        }
        try {
            $result = $this->paymentGateway->return($return_params);
        } catch (\Exception $e) {
            return $this->sendError('P0006', ['message' => $e->getMessage()], 400);
        }

        $status = $result['is_success'] ?? '';
        if ($status == 'ok') {
            return $this->sendResponse($result, 200);
        } else {
            return $this->sendError('P0006', [
                'orderid' => array_get($result, 'orderid'),
                'message' => array_get($result, 'message', 'unknown_error'),
                'href' => array_get($result, 'href')
            ], 400);
        }
    }
}
