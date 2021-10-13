<?php


namespace App\Http\Controllers\V1;


use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\PaymentInitiateService;
use Illuminate\Http\Request;

class TinggController extends BaseController
{
    protected $paymentGateway;
    protected $paymentInitiateService;

    public function __construct(
        PaymentGatewayInterface $paymentGateway,
        PaymentInitiateService $paymentInitiateService
    ) {
        $this->paymentGateway = $paymentGateway;
        $this->paymentInitiateService = $paymentInitiateService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/tingg/redirto",
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
        $t_id = $request->get('t_id');
        try {
            $result = $this->paymentGateway->redirto($t_id);
            return $this->sendResponse($result, 200);
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/v1/tingg/return",
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
            $message = $result['message'] ?? '';
            if ($message == 'transaction_id_not_exists') {
                return $this->sendError('P0011', 'transaction id does not exists', 400);
            } else {
                return $this->sendResponse($result, 200);
            }
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/tingg/notify",
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
        $params = $request->post();
        try {
            $result = $this->paymentGateway->notify($params);
            $message = $result['message'] ?? '';
            if ($message == 'transaction_id_not_exists') {
                return $this->sendError('P0011', 'transaction id does not exists', 400);
            } else {
                return $this->sendResponse($result, 200);
            }
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }
}
