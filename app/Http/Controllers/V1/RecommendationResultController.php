<?php


namespace App\Http\Controllers\V1;


use App\Services\RecommendationResultService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecommendationResultController extends BaseController
{
    private $recommendationResultService;

    public function __construct(
        RecommendationResultService $recommendationResultService
    )
    {
        $this->recommendationResultService = $recommendationResultService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/rcd_result",
     *     tags={"Recommendation Result API"},
     *     description="add a recommendation result",
     *      @OA\Parameter(
     *          name="f_id",
     *          in="query",
     *          description="the tlsconnect f_id",
     *          required=true,
     *          @OA\Schema(type="integer", example="1"),
     *      ),
     *      @OA\Parameter(
     *          name="agent",
     *          in="query",
     *          description="the agent who operate this record",
     *          required=true,
     *          @OA\Schema(type="string", example="test.test"),
     *      ),
     *      @OA\Parameter(
     *          name="sku",
     *          in="query",
     *          description="the sku accepted or denied",
     *          required=true,
     *          @OA\Schema(type="string", example="COURIER"),
     *      ),
     *      @OA\Parameter(
     *          name="result",
     *          in="query",
     *          description="the recommendation result, accept or deny",
     *          required=true,
     *          @OA\Schema(type="string", example="accept"),
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
    public function create(Request $request)
    {
        $params    = [
            'rr_xref_f_id' => $request->input('f_id'),
            'rr_agent'     => $request->input('agent'),
            'rr_sku'       => $request->input('sku'),
            'rr_result'    => $request->input('result')
        ];
        $validator = validator($params, [
            'rr_xref_f_id'   => 'required|integer',
            'rr_agent'  => 'required|string',
            'rr_sku'    => 'required|string',
            'rr_result' => [
                'required',
                'string',
                Rule::in(['accept', 'deny']),
            ]
        ]);
        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $params = $validator->validated();
            $res    = $this->recommendationResultService->create($params);
            return $this->sendResponse($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/rcd_result/{f_id}",
     *     tags={"Recommendation Result API"},
     *     description="get the recommendation result according to f_id",
     *      @OA\Parameter(
     *          name="f_id",
     *          in="path",
     *          description="the tlsconnect f_id",
     *          required=true,
     *          @OA\Schema(type="integer", example="10000"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="get the recommendation result list",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      )
     * )
     */
    public function fetchAll(Request $request)
    {
        $params    = [
            'f_id' => $request->route('f_id')
        ];
        $validator = validator($params, [
            'f_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $params = $validator->validated();
            $res    = $this->recommendationResultService->fetchByFId($params['f_id']);
            return $this->sendResponse($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/rcd_result/{rcd_id}",
     *     tags={"Recommendation Result Delete API"},
     *     description="delete the recommendation results according to rcd_id",
     *      @OA\Parameter(
     *          name="rcd_id",
     *          in="path",
     *          description="the recommendation result id",
     *          required=true,
     *          @OA\Schema(type="integer", example="1"),
     *      ),
     *     @OA\Parameter(
     *          name="rr_deleted_by",
     *          in="query",
     *          description="the agent who delete this record",
     *          required=true,
     *          @OA\Schema(type="string", example="test.test"),
     *      ),
     *     @OA\Parameter(
     *          name="is_soft_delete",
     *          in="query",
     *          description="soft delete this record or not, yes(for soft delte) or no",
     *          required=false,
     *          @OA\Schema(type="string", example="yes"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="deleted the record",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      )
     * )
     */
    public function delete(Request $request)
    {
        $params    = [
            'rr_id' => $request->route('rcd_id'),
            'rr_deleted_by' => $request->input('rr_deleted_by'),
            'is_soft_delete' => $request->input('is_soft_delete', 'yes')
        ];
        $validator = validator($params, [
            'rr_id' => 'required|integer',
            'rr_deleted_by' => 'required|string',
            'is_soft_delete' => [
                'required',
                'string',
                Rule::in(['yes', 'no']),
            ]
        ]);
        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $params = $validator->validated();
            $res    = $this->recommendationResultService->delete($params);
            return $this->sendResponse($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

}
