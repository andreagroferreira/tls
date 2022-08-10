<?php

namespace App\Http\Controllers\V1;

use App\Services\PaymentAccountsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentAccountsController extends BaseController
{
    protected $paymentAccounts;

    public function __construct(PaymentAccountsService $paymentAccounts)
    {
        $this->paymentAccounts = $paymentAccounts;
    }

    public function getPaymentGatewayFieldList()
    {
        $fieldList = [];
        foreach (config('payment_gateway_accounts') as $key => $value) {
            $fieldList[$key]['label'] = $value['label'];
            foreach ($value as $env => $field) {
                if (is_array($field) && $env === 'sandbox') {
                    $fieldList[$key]['sandbox'] = array_filter($field, function ($v){
                        return $v === null;
                    });
                } elseif($env === 'prod') {
                    $fieldList[$key]['prod']    = array_filter($field, function ($v){
                        return $v === null;
                    });
                }
            }
        }

        try{
            return $this->sendResponse($fieldList);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\GET(
     *     path="/api/v1/payment-configurations-list",
     *     tags={"Payment API"},
     *     description="get a payment_configurations",
     *     @OA\Parameter(
     *          name="client",
     *          in="query",
     *          description="define which client you want to fetch",
     *          required=true,
     *          @OA\Schema(type="string", example="de"),
     *      ),
     *     @OA\Parameter(
     *          name="service_type",
     *          in="query",
     *          description="payment_configurations pc_service",
     *          required=true,
     *          @OA\Schema(type="string", example="tls"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="payment_accounts update success",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function fetchList(Request $request) {
        $params    = [
            'client' => $request->input('client'),
            'type'   => $request->input('type')
        ];
        $validator = validator($params, [
            'client' => 'required|string',
            'type'   => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }
        try{
            return $this->sendResponse($this->paymentAccounts->fetchList($params));
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\PUT(
     *     path="/api/v1/payment-configurations",
     *     tags={"Payment API"},
     *     description="update a payment_accounts",
     *      @OA\Parameter(
     *          name="psp_id",
     *          in="query",
     *          description="payment_accounts pa_xref_psp_id",
     *          required=true,
     *          @OA\Schema(type="integer", example="10000"),
     *      ),
     *      @OA\Parameter(
     *          name="pa_name",
     *          in="query",
     *          description="payment_accounts pa_name",
     *          required=true,
     *          @OA\Schema(type="string", example="cmi"),
     *      ),
     *     @OA\Parameter(
     *          name="pa_type",
     *          in="query",
     *          description="payment_accounts pa_type",
     *          required=true,
     *          @OA\Schema(type="string", example="[sandbox, prod]"),
     *      ),
     *     @OA\Parameter(
     *          name="pa_info",
     *          in="query",
     *          description="payment_accounts pa_info.",
     *          required=true,
     *          @OA\Schema(type="json", example=""),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="payment_accounts update success",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function update(Request $request)
    {
        $pa_info = is_array($request->input('pa_info')) ? json_encode($request->input('pa_info')) : $request->input('pa_info');
        $params = [
            'pa_id' => $request->route('pa_id'),
            'pa_type' => $request->input('pa_type'),
            'pa_name' => $request->input('pa_name'),
            'pa_info' => $pa_info,
        ];
        $validator = validator($params, [
            'pa_id' => 'required|integer',
            'pa_type' => Rule::in(['sandbox', 'prod']),
            'pa_name' => 'required|string',
            'pa_info' => [
                'required',
                'bail',
                'json',
            ],
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $result = $this->paymentAccounts->update($params);
            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'error' => 'unknown_error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
