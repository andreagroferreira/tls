<?php

namespace App\Http\Controllers\V1;

use App\Services\RefundService;
use Illuminate\Http\Request;

class RefundController extends BaseController
{
    protected $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/refund",
     *     tags={"Payment API"},
     *     description="create a new refund request",
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function create(Request $request)
    {
        try {
            $refund = $this->refundService->create($request->input());

            if (empty($refund)) {
                return $this->sendError('unknown_error', 'create failed');
            }

            return $this->sendResponse($refund);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }
}
