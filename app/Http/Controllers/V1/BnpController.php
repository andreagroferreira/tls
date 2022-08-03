<?php

namespace App\Http\Controllers\V1;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Services\ApiService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BnpController extends BaseController
{
    private $paymentGateway;
    protected $invoiceService;
    protected $apiService;

    public function __construct(PaymentGatewayInterface $paymentGateway, InvoiceService $invoiceService, ApiService $apiService)
    {
        $this->paymentGateway = $paymentGateway;
        $this->invoiceService = $invoiceService;
        $this->apiService = $apiService;
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

    /**
     * @OA\Post(
     *     path="/api/v1/bnp/receipt",
     *     tags={"Payment API"},
     *     description="return receipt",
     *     @OA\Parameter(
     *          name="order_id",
     *          in="query",
     *          description="the transaction_id",
     *          required=true,
     *          @OA\Schema(type="string", example="DEVELOPMENT20210414-dzALG2be-0000000055"),
     *      ),
     *     @OA\Parameter(
     *          name="action",
     *          in="query",
     *          description="action",
     *          required=true,
     *          @OA\Schema(type="string", example="show/download/send"),
     *      ),
     *     @OA\Parameter(
     *          name="email",
     *          in="query",
     *          description="If action is send, you need to fill in email",
     *          required=false,
     *          @OA\Schema(type="string", example="a@a.com"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="receipt content",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function receipt(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'order_id' => 'required',
                'action' => [
                    'required',
                    Rule::in(['show', 'download', 'send'])
                ],
                'email' => 'required_if:action,send|email'
            ]);

            if ($validator->fails()) {
                return $this->sendError('', $validator->errors()->first());
            }

            $params = $validator->validated();
            $res = $this->invoiceService->getInvoiceFileContent($params['order_id']);
            $res = $res ? base64_encode($res) : '';

            if (!$res) {
                return $this->sendError('', '');
            } else if ($params['action'] == 'send') {
                $response = $this->apiService->callEmailApi('POST', 'send_email', [
                    'to' => $params['email'],
                    'subject' => 'Order ' . $params['order_id'] . ' receipt',
                    'body' => 'Order ' . $params['order_id'] . ' receipt, please check the attachment.',
                    'attachment' => ['pdf' => ['receipt.pdf' => $res]]
                ]);
                return array_get($response, 'status') == 200 ? $this->sendResponse(['content' => 'true']) : $this->sendError('', '');
            } else {
                return $this->sendResponse(['content' => $res]);
            }
        } catch (\Exception $e) {
            return $this->sendError('', $e->getMessage());
        }
    }
}
