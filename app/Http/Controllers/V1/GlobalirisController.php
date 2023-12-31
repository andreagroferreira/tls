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
            $body = $this->paymentGateway->redirto($params);

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
        $params = $request->post();

        try {
            $result = $this->paymentGateway->return($params);
        } catch (\Exception $e) {
            return $this->sendError('P0006', ['message' => $e->getMessage()], 400);
        }
        $status = $result['is_success'] ?? '';
        $message = $result['message'];
        if ($status == 'ok') {
            return $this->sendResponse($result, 200);
        }
        if ($message == 'transaction_id_not_exists') {
            return $this->sendError('P10011', ['message' => 'transaction id does not exists', 'href' => array_get($result, 'href')], 400);
        }
        if ($message == 'signature_verification_failed') {
            return $this->sendError('P10013', ['message' => 'ONLINE PAYMENT, Globaliris: digital signature check failed', 'href' => array_get($result, 'href')], 400);
        }
        if ($message == '101') {
            return $this->sendError('P10018', ['message' => 'Sorry, the transaction has been declined and was not successful.', 'href' => array_get($result, 'href')], 400);
        }
        if ($message == '103') {
            return $this->sendError('P10019', ['message' => 'Sorry, this card has been reported lost or stolen, please contact your bank.', 'href' => array_get($result, 'href')], 400);
        }
        if ($message == '205') {
            return $this->sendError('P10020', ['message' => 'Sorry, there has been a communications error, please try again later.', 'href' => array_get($result, 'href')], 400);
        }

        return $this->sendError('P10006', ['message' => $result['gatewayMessage'] ?? 'unknown_error', 'href' => array_get($result, 'href')], 400);
    }

    /**
     * @OA\Get (
     *     path="/api/v1/globaliris/redirect",
     *     tags={"Payment API"},
     *     description="return reqeust from globaliris",
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
    public function redirect(Request $request)
    {
        return $this->sendResponse([
            'is_success' => $request->get('flag') ?? '',
            'orderid' => $request->get('orderid') ?? '',
            'message' => $request->get('message') ?? '',
            'href' => $request->get('href') ?? '',
        ], 200);
    }
}
