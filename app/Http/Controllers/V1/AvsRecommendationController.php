<?php

namespace App\Http\Controllers\V1;


use App\Services\AvsRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AvsRecommendationController extends BaseController
{
    private $avsRecommendationService;

    public function __construct(
        AvsRecommendationService $avsRecommendationService
    )
    {
        $this->avsRecommendationService = $avsRecommendationService;
    }

    public function fetch(Request $request)
    {
        $params = [
            'f_id' => $request->route('f_id'),
            'client' => $request->route('client'),
            'limit' => $request->get('limit', 6)
        ];
        $validator = validator($params, [
            'f_id' => 'required|integer',
            'client' => [
                'required',
                'string',
                Rule::in(Storage::disk('rules')->allDirectories()),
            ],
            'limit' => 'required|integer'
        ]);
        if($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }
        try {
            $skus = $this->avsRecommendationService->calcRecommendAvs($params);
            return $this->sendResponse($skus);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }
}
