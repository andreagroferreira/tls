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
}
