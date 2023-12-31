<?php

namespace App\Http\Controllers\V1;

use App\Services\QueueService;
use Illuminate\Http\Request;

class QueueController extends BaseController
{
    protected $QueueService;

    public function __construct(QueueService $QueueService)
    {
        $this->QueueService = $QueueService;
    }

    /**
     * @OA\Get(
     *     path="/retry_failed_queue/{queue_name}",
     *     tags={"Payment API"},
     *     description="resend transaction",
     *
     *     @OA\Parameter(
     *          name="queue_name",
     *          in="query",
     *          description="queue name",
     *          required=true,
     *
     *          @OA\Schema(type="string", example="tlscontact_transaction_sync_queue"),
     *      ),
     *
     *      @OA\Response(
     *          response="200",
     *          description="added to transaction queue",
     *
     *          @OA\JsonContent(),
     *      ),
     *
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request, added to transaction queue failed"
     *      ),
     * )
     */
    public function resend(Request $request)
    {
        $params = [
            'queue_name' => $request->route('queue_name'),
        ];
        $validator = validator($params, [
            'queue_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }
        $params = $validator->validated();

        try {
            $result = $this->QueueService->resend($params);
            if (isset($result['error'])) {
                return $this->sendError('fail', $result);
            }

            return $this->sendResponse($result);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/health",
     *     tags={"Email Service"},
     *     description="get jobs volume",
     *
     *     @OA\Parameter(
     *          name="queue_name",
     *          in="query",
     *          description="queue name",
     *          required=false,
     *
     *          @OA\Schema(type="string", example="tlscontact_transaction_sync_queue"),
     *      ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="return the volume count",
     *
     *         @OA\JsonContent(),
     *     ),
     *
     *     @OA\Response(
     *         response="400",
     *         description="Error: bad request, get volume failed"
     *     ),
     * )
     */
    public function health()
    {
        try {
            $result = $this->QueueService->health();

            return $this->sendResponse($result);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }
}
