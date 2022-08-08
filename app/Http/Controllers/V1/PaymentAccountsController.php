<?php

namespace App\Http\Controllers\V1;

use App\Services\PaymentAccountsService;
use App\Services\PaymentConfigurationsService;
use Illuminate\Http\Request;

//header('Access-Control-Allow-Origin:*');
//header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, authorization");

class PaymentAccountsController extends BaseController
{
    private $PaymentAccountsService;
    private $paymentConfigurationsService;

    public function __construct(
        PaymentAccountsService $PaymentAccountsService,
        PaymentConfigurationsService $paymentConfigurationsService
    )
    {
        $this->PaymentAccountsService = $PaymentAccountsService;
        $this->paymentConfigurationsService = $paymentConfigurationsService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payment-accounts",
     *     tags={"Payment API"},
     *     description="Get the paymentgateway list",
     *     @OA\Parameter(
     *          name="pc_id",
     *          in="query",
     *          description="payment_configurations",
     *          required=false,
     *          @OA\Schema(type="integer", example="10"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="get the paymentgateway result list",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      )
     * )
     */
    public function getPaymentAccounts(Request $request)
    {
        try {
            $params = [
                'pc_id' => $request->get('pc_id' ),
            ];
            $validator = validator($params, [
                'pc_id' => 'integer'
            ]);
            if($validator->fails()) {
                return $this->sendError('params error', $validator->errors()->first());
            }
            $all_payment_config = $this->PaymentAccountsService->fetch()->toArray();
            $exist_payment_config = $this->getExistsConfigs($params['pc_id']);
            $res = array_filter($all_payment_config, function ($v, $k) use ($exist_payment_config) {
                foreach ($exist_payment_config as $key => $val) {
                    if ($val['pa_name'] == $v['pa_name']) {
                        return false;
                    }
                }
                return true;
            }, ARRAY_FILTER_USE_BOTH);
            $payment_config = array_values($res);
            return $this->sendResponse($payment_config);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payment-exists-config",
     *     tags={"Payment API"},
     *     description="Get the issuer exists payment-config",
     *     @OA\Parameter(
     *          name="pc_id",
     *          in="query",
     *          description="payment_configurations",
     *          required=false,
     *          @OA\Schema(type="integer", example="10"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="get the paymentgateway result list",
     *          @OA\JsonContent(),
     *      ),
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
                'pc_id' => $request->get('pc_id' ),
            ];
            $validator = validator($params, [
                'pc_id' => 'integer'
            ]);
            if($validator->fails()) {
                return $this->sendError('params error', $validator->errors()->first());
            }
            $res = $this->getExistsConfigs($params['pc_id']);
            return $this->sendResponse($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    private function getExistsConfigs($pc_id){
        $payment_configs = $this->paymentConfigurationsService->fetch($pc_id);
        $pa_type = env('APP_ENV') === 'production' ? 'prod' : 'sandbox';
        $paymentConfig = [];
        foreach ($payment_configs as $k=>$v){
            $res = $this->PaymentAccountsService->fetchById($v['pc_xref_pa_id']);
            if($res['pa_type'] === $pa_type){
                $paymentConfig['pa_id'] = $res['pa_id'];
                $paymentConfig['pa_name'] = $res['pa_name'];
                $paymentConfig['is_show'] = $v['pc_tech_deleted'] ? true : false;
                $payConfig[] = $paymentConfig;
            }
        }
        return $payConfig;
    }

}
