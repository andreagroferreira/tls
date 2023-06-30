<?php

namespace App\Http\Controllers\V1;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use Illuminate\Http\Request;

class PaysoftController extends BaseController
{
    private $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/paysoft/redirto",
     *     tags={"Payment API"},
     *     description="return reqeust from paysoft",
     *
     *     @OA\Parameter(
     *          name="t_id",
     *          in="query",
     *          description="transaction id",
     *          required=true,
     *
     *          @OA\Schema(type="integer", example="10000"),
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="transaction created",
     *
     *          @OA\JsonContent(),
     *      ),
     *
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function redirto(Request $request)
    {
        $params = $request->post();

        try {
            $result = $this->paymentGateway->redirto($params);
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
     *     path="/api/v1/paysoft/notify",
     *     tags={"Payment API"},
     *     description="return reqeust from paygate",
     *
     *      @OA\Response(
     *          response="200",
     *          description="transaction created",
     *
     *          @OA\JsonContent(),
     *      ),
     *
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function notify(Request $request)
    {
        $params = $request->input();
        if (blank($params)) {
            return $this->sendError('P0006', 'Illegal parameter');
        }

        try {
            $result = $this->paymentGateway->notify($params);
            if ($result) {
                return $this->sendResponse($result);
            }

            return $this->sendError('', '');
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/paysoft/return",
     *     tags={"Payment API"},
     *     description="return reqeust from paysoft",
     *
     *      @OA\Response(
     *          response="200",
     *          description="transaction created",
     *
     *          @OA\JsonContent(),
     *      ),
     *
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
            $this->sendError('P0009', ['message' => 'no_data_received'], 400);
        }

        try {
            $result = $this->paymentGateway->return($return_params);
        } catch (\Exception $e) {
            return $this->sendError('P0006', ['message' => $e->getMessage()], 400);
        }

        $status = $result['is_success'] ?? '';
        if ($status == 'ok') {
            return $this->sendResponse($result, 200);
        }

        return $this->sendError('P0006', ['message' => array_get($result, 'message', 'unknown_error'), 'href' => array_get($result, 'href')], 400);
    }
}
