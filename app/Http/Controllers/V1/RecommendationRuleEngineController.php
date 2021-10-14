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

    public function fetch(Request $request) {
        $params = [
            'client' => $request->input('client'),
            'issuer' => $request->input('issuer'),
            'Visa Type' => $request->input('visa_type'),
            'Travel Purpose' => $request->input('travel_purpose'),
            'Visa SubType (UK)' => $request->input('visa_sub_type'),
            'In a group' => $request->input('in_a_group', ''),
            'Age' => $request->input('age'),
            'City of residence' => $request->input('city_of_residence', ''),
            'Nationality' => $request->input('nationality'),
            'Account Type' => $request->input('account_type'),
            'Step' => $request->input('step'),
            'top' => $request->input('top', 6)
        ];
        $validator = validator($params, [
            'client' => [
                'required',
                Rule::in(Storage::disk('rules')->allDirectories()),
            ],
            'issuer' => 'string|regex:/^[a-z]{2}[A-Z]{3}2[a-z]{2}$/',
            'Visa Type' => 'string|required',
            'Travel Purpose' => 'string|required',
            'Visa SubType (UK)' => 'string|required',
            'In a group' => [
                Rule::in(['yes', 'no', '']),
            ],
            'Age' => 'integer|between:0,150',
            'City of residence' => 'string',
            'Nationality' => 'string|regex:/^[a-z]{2}$/',
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
