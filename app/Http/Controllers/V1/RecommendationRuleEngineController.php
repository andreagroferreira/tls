<?php

namespace App\Http\Controllers\V1;

use App\Services\RecommendationRuleEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RecommendationRuleEngineController extends BaseController
{
    private $rcdRuleEngineService;

    public function __construct(
        RecommendationRuleEngineService $rcdRuleEngineService
    )
    {
        $this->rcdRuleEngineService = $rcdRuleEngineService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/rcd_rule",
     *     tags={"Payment API"},
     *     description="get the recommendation services by rule engine",
     *      @OA\Parameter(
     *          name="issuer",
     *          in="query",
     *          description="issuer code",
     *          required=true,
     *          @OA\Schema(type="string", example="esMAD2uk"),
     *      ),
     *      @OA\Parameter(
     *          name="visa_type",
     *          in="query",
     *          description="applicant's visa type",
     *          required=true,
     *          @OA\Schema(type="string", example="short_stay"),
     *      ),
     *      @OA\Parameter(
     *          name="travel_purpose",
     *          in="query",
     *          description="applicant's travel purpose",
     *          required=true,
     *          @OA\Schema(type="string", example="business"),
     *      ),
     *      @OA\Parameter(
     *          name="age",
     *          in="query",
     *          description="applicant's age",
     *          required=true,
     *          @OA\Schema(type="integer", example="6"),
     *      ),
     *      @OA\Parameter(
     *          name="nationality",
     *          in="query",
     *          description="applicant's nationality code",
     *          required=true,
     *          @OA\Schema(type="string", example="cn"),
     *      ),
     *      @OA\Parameter(
     *          name="account_type",
     *          in="query",
     *          description="applicant's account type",
     *          required=true,
     *          @OA\Schema(type="string", example="INDI"),
     *      ),
     *      @OA\Parameter(
     *          name="step",
     *          in="query",
     *          description="the tlsconnect f_id",
     *          required=true,
     *          @OA\Schema(type="integer", example="10001"),
     *      ),
     *      @OA\Parameter(
     *          name="visa_sub_type",
     *          in="query",
     *          description="applicant's sub visa type",
     *          required=false,
     *          @OA\Schema(type="string", example="ext"),
     *      ),
     *      @OA\Parameter(
     *          name="top",
     *          in="query",
     *          description="number of recommended services",
     *          required=false,
     *          @OA\Schema(type="integer", example="6"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="return the recommended services",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      )
     * )
     */
    public function fetch(Request $request) {
        $params = [
            'issuer' => $request->input('issuer'),
            'Visa Type' => $request->input('visa_type'),
            'Travel Purpose' => $request->input('travel_purpose'),
            'Visa SubType (UK)' => $request->input('visa_sub_type'),
            'In a group' => $request->input('in_a_group'),
            'Age' => $request->input('age'),
            'City of residence' => $request->input('city_of_residence'),
            'Nationality' => $request->input('nationality'),
            'Account Type' => $request->input('account_type'),
            'Step' => $request->input('step'),
            'top' => $request->input('top', 6)
        ];
        $validator = validator($params, [
            'issuer' => 'string|regex:/^[a-z]{2}[A-Z]{3}2[a-z]{2}$/',
            'Visa Type' => 'string|required',
            'Travel Purpose' => 'string|required',
            'Visa SubType (UK)' => 'string|nullable',
            'In a group' => [
                'nullable',
                Rule::in(['yes', 'no', ''])
            ],
            'Age' => 'integer|between:0,150|nullable',
            'City of residence' => 'string|nullable',
            'Nationality' => 'string|regex:/^[a-z]{2}$/|nullable',
            'Account Type' => [
                'required',
                Rule::in(['INDI', 'COMP', 'ADS', 'AGEN'])
            ],
            'Step' => [
                Rule::in(['Welcome', 'Doc', 'Bio'])
            ],
            'top' => 'integer|Min:1'
        ]);

        if($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $res = $this->rcdRuleEngineService->fetchRules($params);
            return $this->sendResponse($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }
}
