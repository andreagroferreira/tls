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
     *     path="/api/v1/avs_recommendation/{client}/{f_id}",
     *     tags={"Payment API"},
     *     description="add a recommendation result",
     *      @OA\Parameter(
     *          name="client",
     *          in="query",
     *          description="the tlsconnect client",
     *          required=true,
     *          @OA\Schema(type="string", example="be"),
     *      ),
     *      @OA\Parameter(
     *          name="f_id",
     *          in="query",
     *          description="the tlsconnect f_id",
     *          required=true,
     *          @OA\Schema(type="integer", example="10001"),
     *      ),
     *      @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          description="get the recommend limited avs, default for 6",
     *          required=false,
     *          @OA\Schema(type="integer", example="6"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="create a recommendation result record success",
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
