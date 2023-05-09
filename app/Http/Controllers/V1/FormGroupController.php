<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Services\FormGroupService;

class FormGroupController extends BaseController
{
    private $formGroupService;

    public function __construct(FormGroupService $formGroupService)
    {
        $this->formGroupService = $formGroupService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/form_group/{fg_id}",
     *     tags={"TLS API"},
     *     description="Get form group data",
     *      @OA\Parameter(
     *          name="fg_id",
     *          in="path",
     *          description="the form group id in database",
     *          required=true,
     *          @OA\Schema(type="integer", example=1),
     *      ),
     *      @OA\Parameter(
     *          name="client",
     *          in="query",
     *          description="define which client you want to create the acccount in",
     *          required=true,
     *          @OA\Schema(type="string", example="be"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="get the form group information",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request, form group not found"
     *      ),
     * )
     */
    public function fetch(Request $request)
    {
        $params = $request->all();
        $fg_id  = $request->route('fg_id');
        if (blank($fg_id) || !isset($params['client'])) {
            return $this->sendError('miss_fields', 'missing fg_id or client');
        }
        try {
            $form_group = $this->formGroupService->fetch($fg_id, $params['client']);
            if (empty($form_group)) {
                return $this->sendError('form_group_not_exists', 'form_group_not_exists');
            }
            return $this->sendResponse($form_group);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }
}
