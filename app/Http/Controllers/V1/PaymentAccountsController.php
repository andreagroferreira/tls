<?php

namespace App\Http\Controllers\V1;

use App\Services\PaymentAccountsService;
use Illuminate\Http\Request;

class PaymentAccountsController extends BaseController
{
    protected $paymentAccountsService;

    public function __construct(PaymentAccountsService $paymentAccounts)
    {
        $this->paymentAccountsService = $paymentAccounts;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payment-gateway-field-list",
     *     tags={"Payment API"},
     *     description="Get field details for all payment accounts.",
     *
     *      @OA\Response(
     *          response="200",
     *          description="get the payment_accounts information",
     *
     *          @OA\JsonContent(),
     *      ),
     *
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     *      @OA\Response(
     *          response="404",
     *          description="payment_accounts not found"
     *      ),
     * )
     */
    public function getPaymentGatewayFieldList()
    {
        $fieldList = [];
        foreach (config('payment_gateway_accounts') as $key => $value) {
            $fieldList[$key]['label'] = $value['label'];
            foreach ($value as $env => $field) {
                if (!is_array($field)) {
                    continue;
                }
                $fieldList[$key][$env] = array_filter($field, function ($v) {
                    return $v === null;
                });
            }
        }

        try {
            return $this->sendResponse($fieldList);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payment-account/{pa_id}",
     *     tags={"Payment API"},
     *     description="get the payment_accounts details according to pa_id",
     *
     *      @OA\Parameter(
     *          name="pa_id",
     *          in="path",
     *          description="the payment_accounts pa_id",
     *          required=true,
     *
     *          @OA\Schema(type="integer", example="10000"),
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="get the payment_accounts information",
     *
     *          @OA\JsonContent(),
     *      ),
     *
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     *      @OA\Response(
     *          response="404",
     *          description="payment_accounts not found"
     *      ),
     * )
     */
    public function fetch(Request $request)
    {
        $params = [
            'pa_id' => $request->route('pa_id'),
        ];
        $validator = validator($params, [
            'pa_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $result = $this->paymentAccountsService->fetch($validator->validated());
            if ($result) {
                return $this->sendResponse($result);
            }

            return $this->sendEmptyResponse(204);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\PUT(
     *     path="/api/v1/payment-account",
     *     tags={"Payment API"},
     *     description="update a payment_accounts",
     *
     *      @OA\Parameter(
     *          name="psp_id",
     *          in="query",
     *          description="payment_accounts pa_xref_psp_id",
     *          required=true,
     *
     *          @OA\Schema(type="integer", example="10000"),
     *      ),
     *
     *      @OA\Parameter(
     *          name="pa_name",
     *          in="query",
     *          description="payment_accounts pa_name",
     *          required=true,
     *
     *          @OA\Schema(type="string", example="cmi"),
     *      ),
     *
     *     @OA\Parameter(
     *          name="pa_type",
     *          in="query",
     *          description="payment_accounts pa_type",
     *          required=true,
     *
     *          @OA\Schema(type="string", example="[sandbox, prod]"),
     *      ),
     *
     *     @OA\Parameter(
     *          name="pa_info",
     *          in="query",
     *          description="payment_accounts pa_info.",
     *          required=true,
     *
     *          @OA\Schema(type="json", example=""),
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="payment_accounts update success",
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
    public function update(Request $request)
    {
        $params = [
            'pa_id' => $request->route('pa_id'),
            'pa_name' => $request->input('pa_name'),
            'pa_info' => $request->input('pa_info') ? json_encode($request->input('pa_info')) : '',
        ];
        $validator = validator($params, [
            'pa_id' => 'required|integer',
            'pa_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $result = $this->paymentAccountsService->update($params);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'error' => 'unknown_error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payment-account",
     *     tags={"Payment API"},
     *     description="add exists payment account",
     *
     *      @OA\Parameter(
     *          name="pa_xref_psp_id",
     *          in="query",
     *          description="payment_service_providers id",
     *          required=true,
     *
     *          @OA\Schema(type="number", example="2"),
     *      ),
     *
     *      @OA\Parameter(
     *          name="pa_name",
     *          in="query",
     *          description="payment_accounts name",
     *          required=true,
     *
     *          @OA\Schema(type="string", example="alipay cnBSJ2be prod"),
     *      ),
     *
     *      @OA\Parameter(
     *          name="pa_type",
     *          in="query",
     *          description="payment_type option",
     *          required=true,
     *
     *          @OA\Schema(type="string", example="prod, sandbox"),
     *      ),
     *
     *     @OA\Parameter(
     *          name="pa_info",
     *          in="query",
     *          description="payment_accounts pa_info.",
     *          required=true,
     *
     *          @OA\Schema(type="json", example=""),
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="return upload success",
     *
     *          @OA\JsonContent(),
     *      ),
     *
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      )
     * )
     */
    public function create(Request $request)
    {
        $params = [
            'pa_xref_psp_id' => $request->input('pa_xref_psp_id'),
            'pa_name' => $request->input('pa_name'),
            'pa_type' => $request->input('pa_type'),
            'pa_info' => $request->input('pa_info') ? json_encode($request->input('pa_info')) : '',
        ];
        if ($params['pa_type'] === 'pay_later') {
            $validator = validator($params, [
                'pa_xref_psp_id' => 'required|integer',
                'pa_name' => 'required|string',
                'pa_type' => 'required|string|in:production,sandbox,pay_later',
            ]);
        } else {
            $validator = validator($params, [
                'pa_xref_psp_id' => 'required|integer',
                'pa_name' => 'required|string',
                'pa_type' => 'required|string|in:production,sandbox',
                'pa_info' => [
                    'required',
                    'bail',
                    'json',
                ],
            ]);
        }

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $result = $this->paymentAccountsService->create($params);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'error' => 'unknown_error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payment-service-providers",
     *     tags={"Payment API"},
     *     description="get the payment_service_providers details",
     *
     *      @OA\Response(
     *          response="200",
     *          description="get the payment_service_providers  information",
     *
     *          @OA\JsonContent(),
     *      ),
     *
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     *      @OA\Response(
     *          response="404",
     *          description="payment_service_providers not found"
     *      ),
     * )
     */
    public function fetchServiceList(Request $request)
    {
        try {
            $result = $this->paymentAccountsService->fetchPaymentServiceProvidersList();
            if ($result) {
                return $this->sendResponse($result);
            }

            return $this->sendEmptyResponse(204);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }
}
