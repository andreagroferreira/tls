<?php


namespace App\Http\Controllers\V1;


use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use Illuminate\Http\Request;

class CmiController extends BaseController
{
    protected $paymentGateway;

    public function __construct(
        PaymentGatewayInterface $paymentGateway) {
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cmi/redirto",
     *     tags={"Payment API"},
     *     description="redirect request from CMI",
     *      @OA\Parameter(
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
        $t_id = $request->input('t_id');
        try {
            $body = $this->paymentGateway->redirto($t_id);
            return $this->sendResponse($body, 200);
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }

    }
    /**
     * @OA\Post(
     *     path="/api/v1/cmi/return",
     *     tags={"Payment API"},
     *     description="return reqeust from CMI",
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
     *     path="/api/v1/cmi/notify",
     *     tags={"Payment API"},
     *     description="background callback from CMI",
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
        $params = $request->post();
        try {
            $notify_result = $this->paymentGateway->notify($params);
            // here notify the payment gateway by echo a string
            return $this->sendResponse($notify_result, 200);
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }
}