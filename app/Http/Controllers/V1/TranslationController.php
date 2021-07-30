<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Services\TranslationService;

class TranslationController extends BaseController
{
    protected $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/translation/{name}",
     *     tags={"Payment API"},
     *     description="get the translation content",
     *      @OA\Parameter(
     *          name="name",
     *          in="path",
     *          description="the translation name",
     *          required=true,
     *          @OA\Schema(type="string", example="payment"),
     *      ),
     *     @OA\Parameter(
     *          name="lang",
     *          in="query",
     *          description="translation lang",
     *          required=true,
     *          @OA\Schema(type="string", example="en"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="get the translation information",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     *      @OA\Response(
     *          response="404",
     *          description="transaction not found"
     *      ),
     * )
     */
    public function fetch(Request $request)
    {
        $params = [
            'name' => $request->route('name'),
            'lang' => $request->input('lang')
        ];
        $validator = validator($params, [
            'name' => 'required|string',
            'lang' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }
        $validated = $validator->validated();
        try {
            $res = $this->translationService->getTranslation($validated['name'], $validated['lang']);
            if ($res) {
                return $this->sendResponse($res);
            } else {
                return $this->sendEmptyResponse(404);
            }
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

}
