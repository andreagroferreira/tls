<?php


namespace App\Http\Controllers\V1;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use Illuminate\Http\Request;

class GlobalirisController extends BaseController
{
    private $paymentGateway;

    public function __construct(
        PaymentGatewayInterface $paymentGateway
    ) {
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * @OA\Get (
     *     path="/api/v1/globaliris/redirto",
     *     tags={"Payment API"},
     *     description="return reqeust from globaliris",
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
        $orderId = $request->post('t_id');
        try {
            $body = $this->paymentGateway->redirto($orderId);
            return $this->sendResponse($body, 200);
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
    }
    /**
     * @OA\Get (
     *     path="/api/v1/globaliris/return",
     *     tags={"Payment API"},
     *     description="return reqeust from globaliris",
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
        } catch (\Exception $e) {
            return $this->sendError('P0006', $e->getMessage(), 400);
        }
        $status = $result['is_success'] ?? '';
        $message = $result['message'];
        if ($status == 'ok') {
            return $this->sendResponse($result, 200);
        } else if ($message == 'transaction_id_not_exists') {
            return $this->sendError('P10011', 'transaction id does not exists', 400);
        } else if ($message == 'signature_verification_failed') {
            return $this->sendError('P10013', 'ONLINE PAYMENT, Globaliris: digital signature check failed', 400);
        } else if ($message == '101') {
            return $this->sendError('P10018', 'Sorry, the transaction has been declined and was not successful.', 400);
        } else if ($message == '103') {
            return $this->sendError('P10019', 'Sorry, this card has been reported lost or stolen, please contact your bank.', 400);
        } else if ($message == '205') {
            return $this->sendError('P10020', 'Sorry, there has been a communications error, please try again later.', 400);
        } else {
            return $this->sendError('P10006', 'unknown_error', 400);
        }
    }
    /**
     * @OA\Get (
     *     path="/api/v1/globaliris/redirect",
     *     tags={"Payment API"},
     *     description="return reqeust from globaliris",
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
    public function redirect(Request $request) {
        return $this->sendResponse([
            'is_success' => $request->get('flag') ?? '',
            'orderid' => $request->get('orderid') ?? '',
            'message' => $request->get('message') ?? '',
            'href' => $request->get('href') ?? ''
        ], 200);
    }

}
