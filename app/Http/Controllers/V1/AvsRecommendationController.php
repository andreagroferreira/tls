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

    /**
     * @OA\Post(
     *     path="/api/v1/avs_recommendation/{f_id}",
     *     tags={"Payment API"},
     *     description="get the services by directus recommendation priority",
     *      @OA\Parameter(
     *          name="f_id",
     *          in="path",
     *          description="the tlsconnect f_id",
     *          required=true,
     *          @OA\Schema(type="integer", example="10001"),
     *      ),
     *      @OA\Parameter(
     *          name="step",
     *          in="query",
     *          description="the counter location of agent, only accept Welcome, Doc or Bio",
     *          required=true,
     *          @OA\Schema(type="string", example="Welcome"),
     *      ),
     *      @OA\Parameter(
     *          name="source",
     *          in="query",
     *          description="the data source of recommend avs, only accept directus or rule_engine, default for directus",
     *          required=false,
     *          @OA\Schema(type="string", example="rule_engine"),
     *      ),
     *      @OA\Parameter(
     *          name="refresh_cache",
     *          in="query",
     *          description="refresh cache",
     *          required=false,
     *          @OA\Schema(type="string", example="false"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="get the recommended service success",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      )
     * )
     */
    public function fetch(Request $request)
    {
        $params = [
            'f_id' => $request->route('f_id'),
            'source' => $request->get('source', 'directus'),
            'step' => $request->get('step'),
            'refresh_cache' => $request->boolean('refresh_cache')
        ];
        $validator = validator($params, [
            'f_id' => 'required|integer',
            'refresh_cache' => 'nullable',
            'source' => [
                'required',
                Rule::in(['rule_engine', 'directus'])
            ],
            'step' => [
                'required',
                Rule::in(['Welcome', 'Doc', 'Bio'])
            ]
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
