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
     *          name="limit",
     *          in="query",
     *          description="number of recommended services, default for 6",
     *          required=false,
     *          @OA\Schema(type="integer", example="6"),
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
            'limit' => $request->get('limit', 6),
            'source' => $request->get('source', 'directus'),
            'step' => $request->get('step')
        ];
        $validator = validator($params, [
            'f_id' => 'required|integer',
            'limit' => 'required|integer',
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
