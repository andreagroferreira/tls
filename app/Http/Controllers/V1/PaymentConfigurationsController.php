<?php

namespace App\Http\Controllers\V1;

use App\Services\PaymentConfigurationsService;
use Illuminate\Http\Request;

class PaymentConfigurationsController extends BaseController
{
    protected $paymentConfigurations;

    public function __construct(
        PaymentConfigurationsService $paymentConfigurations
    ) {
        $this->paymentConfigurationsService = $paymentConfigurations;
    }

    /**
     * @OA\GET(
     *     path="/api/v1/payment-configurations-list",
     *     tags={"Payment API"},
     *     description="get a payment_configurations",
     *
     *     @OA\Parameter(
     *          name="client",
     *          in="query",
     *          description="define which client you want to fetch",
     *          required=true,
     *
     *          @OA\Schema(type="string", example="de"),
     *      ),
     *
     *     @OA\Parameter(
     *          name="service_type",
     *          in="query",
     *          description="payment_configurations pc_service",
     *          required=true,
     *
     *          @OA\Schema(type="string", example="tls"),
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="payment_configurations update success",
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
    public function fetchList(Request $request)
    {
        $params = [
            'client' => $request->input('client'),
            'type' => $request->input('type'),
        ];
        $validator = validator($params, [
            'client' => 'required|string',
            'type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            return $this->sendResponse($this->paymentConfigurationsService->fetchList($params));
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/location-config",
     *     tags={"Payment API"},
     *     description="Get the issuer exists payment-config",
     *
     *     @OA\Parameter(
     *          name="pc_id",
     *          in="query",
     *          description="payment_configurations",
     *          required=false,
     *
     *          @OA\Schema(type="integer", example="10"),
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="get the paymentgateway result list",
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
    public function getPaymentExistsConfig(Request $request)
    {
        try {
            $params = [
                'pc_id' => $request->get('pc_id'),
            ];
            $validator = validator($params, [
                'pc_id' => 'integer',
            ]);
            if ($validator->fails()) {
                return $this->sendError('params error', $validator->errors()->first());
            }
            $res = $this->paymentConfigurationsService->getExistsConfigs($params['pc_id']);

            return $this->sendResponse($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payment-gateway-types/{city}",
     *     tags={"Payment API"},
     *     description="Get types (gov,tls) of payment gateway by city, used by eCommerce to show multiple baskets",
     *
     *     @OA\Parameter(
     *          name="city",
     *          in="path",
     *          description="Unique 3 letters City code per client",
     *          required=true,
     *
     *          @OA\Schema(type="string", example="CAI"),
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="get the paymentgateway list types (tls,gov)",
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
    public function getPaymentGatewayTypesByCity(Request $request): object
    {
        try {
            $params = [
                'city' => $request->route('city'),
            ];
            $validator = validator($params, [
                'city' => 'required|string',
            ]);
            if ($validator->fails()) {
                return $this->sendError('params error', $validator->errors()->first());
            }
            $res = $this->paymentConfigurationsService->fetchPaymentGatewayTypes($params['city']);

            return $this->sendResponse($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payment-config",
     *     tags={"Payment API"},
     *     description="add exists payment config",
     *
     *      @OA\Parameter(
     *          name="pc_id",
     *          in="query",
     *          description="payment_configurations id",
     *          required=true,
     *
     *          @OA\Schema(type="number", example="123"),
     *      ),
     *
     *      @OA\Parameter(
     *          name="pa_id",
     *          in="query",
     *          description="payment_accounts id",
     *          required=true,
     *
     *          @OA\Schema(type="number", example="123"),
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
    public function addPaymentConfig(Request $request)
    {
        $params = [
            'pc_id' => $request->input('pc_id'),
            'data' => $request->input('data'),
        ];
        $validator = validator($params, [
            'pc_id' => 'required|integer',
            'data' => 'required|array',
        ]);
        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }
        $pc_infos = $this->paymentConfigurationsService->fetchById($params['pc_id']);

        try {
            $data = $params['data'];
            foreach ($data as $k => $v) {
                $params_create = [
                    'pc_xref_pa_id' => $v['pa_id'],
                    'pc_project' => $pc_infos['pc_project'],
                    'pc_country' => $pc_infos['pc_country'],
                    'pc_city' => $pc_infos['pc_city'],
                    'pc_service' => $pc_infos['pc_service'],
                    'pc_is_active' => $v['is_show'],
                ];
                $this->paymentConfigurationsService->save($params_create);
            }

            return $this->sendResponse([
                'status' => 'success',
                'message' => 'Save successful!',
            ]);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\POST (
     *     path="/api/v1/payment-configurations",
     *     tags={"Payment API"},
     *     description="create a payment_configurations",
     *
     *     @OA\Parameter(
     *          name="client",
     *          in="query",
     *          description="define which client you want to fetch",
     *          required=true,
     *
     *          @OA\Schema(type="string", example="de"),
     *      ),
     *
     *     @OA\Parameter(
     *          name="country",
     *          in="query",
     *          description="define which country you want to fetch",
     *          required=true,
     *
     *          @OA\Schema(type="string", example="eg"),
     *      ),
     *
     *     @OA\Parameter(
     *          name="city",
     *          in="query",
     *          description="define which city you want to fetch",
     *          required=true,
     *
     *          @OA\Schema(type="string", example="CAI"),
     *      ),
     *
     *     @OA\Parameter(
     *          name="service",
     *          in="query",
     *          description="payment_configurations pc_service",
     *          required=true,
     *
     *          @OA\Schema(type="string", example="tls"),
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="payment_configurations create success",
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
    public function create(Request $request)
    {
        $params = [
            'pc_project' => $request->input('client'),
            'pc_country' => $request->input('country'),
            'pc_city' => $request->input('city'),
            'pc_service' => $request->input('service'),
        ];
        $validator = validator($params, [
            'pc_project' => 'required|string',
            'pc_country' => 'required|string',
            'pc_city' => 'required|string',
            'pc_service' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            return $this->sendResponse($this->paymentConfigurationsService->create($params));
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/location-available-accounts",
     *     tags={"Payment API"},
     *     description="Get the paymentgateway list",
     *
     *     @OA\Parameter(
     *          name="pc_id",
     *          in="query",
     *          description="payment_configurations",
     *          required=false,
     *
     *          @OA\Schema(type="integer", example="10"),
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="get the paymentgateway result list",
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
    public function getPaymentAccounts(Request $request)
    {
        $params = [
            'pc_id' => $request->get('pc_id'),
        ];
        $validator = validator($params, [
            'pc_id' => 'integer',
        ]);
        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $res = $this->paymentConfigurationsService->paymentAccount($params);

            return $this->sendResponse($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\POST (
     *     path="/api/v1/payment-configurations/{pc_id}",
     *     tags={"Payment API"},
     *     description="delete a payment_configuration",
     *
     *     @OA\Parameter(
     *          name="pc_id",
     *          in="path",
     *          description="the payment_configurations pc_id",
     *          required=true,
     *
     *          @OA\Schema(type="integer", example="10000"),
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="payment_configuration delete success",
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
    public function delete(Request $request)
    {
        $params = [
            'pc_id' => $request->route('pc_id'),
        ];
        $validator = validator($params, [
            'pc_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            return $this->sendResponse($this->paymentConfigurationsService->remove($params));
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }
}
