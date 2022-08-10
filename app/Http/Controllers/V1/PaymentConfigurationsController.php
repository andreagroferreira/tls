<?php

namespace App\Http\Controllers\V1;

use App\Services\PaymentConfigurationsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentConfigurationsController extends BaseController
{
    protected $paymentConfigurations;

    public function __construct(PaymentConfigurationsService $paymentConfigurations)
    {
        $this->paymentConfigurations = $paymentConfigurations;
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
            return $this->sendResponse($this->paymentConfigurations->fetchList($params));
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
            $result = $this->paymentConfigurations->update($params);
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
     *     path="/api/v1/add-payment-config",
     *     tags={"Payment API"},
     *     description="add exists payment config",
     *      @OA\Parameter(
     *          name="pc_id",
     *          in="query",
     *          description="payment_configurations id",
     *          required=true,
     *          @OA\Schema(type="number", example="123"),
     *      ),
     *      @OA\Parameter(
     *          name="pa_id",
     *          in="query",
     *          description="payment_accounts id",
     *          required=true,
     *          @OA\Schema(type="number", example="123"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="return upload success",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      )
     * )
     */
    public function addPaymentConfig(Request $request)
    {
        $params = [
            'pc_id' => $request->input('pc_id'),
            'data' => $request->input('data'),
        ];
        $validator = validator($params, [
            'pc_id'   => 'required|integer',
            'data'  => 'required|array',
        ]);
        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }
        $pc_infos = $this->paymentConfigurations->fetchById($params['pc_id']);
        try {
            $data = $params['data'];
            foreach ($data as $k=>$v){
                $params_create = [
                    'pc_xref_pa_id' => $v['pa_id'],
                    'pc_project' => $pc_infos['pc_project'],
                    'pc_country' => $pc_infos['pc_country'],
                    'pc_city' => $pc_infos['pc_city'],
                    'pc_service' => $pc_infos['pc_service'],
                    'pc_tech_deleted' => !$v['is_show'],
                ];
                $this->paymentConfigurations->save($params_create);
            }
            return $this->sendResponse([
                'status' => 'success',
                'message' => 'Save successful!'
            ]);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }
}
