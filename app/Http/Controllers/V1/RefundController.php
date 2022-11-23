<?php

namespace App\Http\Controllers\V1;

use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
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

    /**
     * @OA\Get (
     *     path="/api/v1/transaction_items_and_refunds/{ti_xref_f_id}",
     *     tags={"Payment API"},
     *     description=" Get all Refund Transaction Items",
     *     @OA\Parameter(
     *          name="ti_xref_f_id",
     *          in="path",
     *          description="Form ID",
     *          required=true,
     *          @OA\Schema(type="integer", example="10000"),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function getRefundTransactionItems(Request $request): JsonResponse
    {
        $params = [
            'ti_xref_f_id' => $request->route('ti_xref_f_id'),
        ];

        $validator = validator($params, [
            'ti_xref_f_id' => 'integer',
        ]);
        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $res = $this->refundService->getRefundTransactionItems($validator->validated());
            if (empty($res)) {
                return $this->sendError('not found', 'Transaction not found or status is not done');
            }

            return $this->sendResponse($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Get (
     *     path="/api/v1/refund/{r_id}",
     *     tags={"Payment API"},
     *     description="Get Refund request details with Transaction & Refund Items",
     *     @OA\Parameter(
     *          name="r_id",
     *          in="path",
     *          description="Refund ID",
     *          required=true,
     *          @OA\Schema(type="integer", example="1"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Got Refund request details with Transaction & Refund Items",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function getRefundRequest(Request $request): JsonResponse
    {
        $params = [
            'r_id' => $request->route('r_id'),
        ];

        $validator = validator($params, [
            'r_id' => 'integer',
        ]);
        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $res = $this->refundService->getRefundRequest($validator->validated());
            if (empty($res)) {
                return $this->sendError('not found', 'Refund request not found');
            }

            return $this->sendResponse($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }
}
