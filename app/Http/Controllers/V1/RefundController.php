<?php

namespace App\Http\Controllers\V1;

use App\Services\RefundService;
use App\Services\TransactionItemsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefundController extends BaseController
{
    /**
     * @var RefundService
     */
    protected $refundService;

    /**
     * @var TransactionItemsService
     */
    protected $transactionItemsService;

    public function __construct(RefundService $refundService, TransactionItemsService $transactionItemsService)
    {
        $this->refundService = $refundService;
        $this->transactionItemsService = $transactionItemsService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/refund",
     *     tags={"Payment API"},
     *     description="create a new refund request",
     *     @OA\Parameter(
     *          name="agent",
     *          in="query",
     *          description="agent email ID",
     *          required=false,
     *          @OA\Schema(type="string", example="test@test.com"),
     *      ),
     *      @OA\Parameter(
     *          name="reason",
     *          in="query",
     *          description="the reason for refund",
     *          required=false,
     *          @OA\Schema(type="string", example="other"),
     *      ),
     *      @OA\Parameter(
     *          name="items",
     *          in="query",
     *          description="Refund items",
     *          required=true,
     *          @OA\Schema(type="string", example=""),
     *      ),
     *     @OA\Response(
     *          response="200",
     *          description="Refund Request created",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $items = is_array($request->input('items')) ? json_encode($request->input('items')) : $request->input('items');
            $validator = $this->validateRefundRequest($items, $request->except('items'));
            if ($validator->fails()) {
                return $this->sendError('params error', $validator->errors()->first());
            }
            $params = $validator->validated();
            $itemsArray = json_decode($items, true);
            $refundItem = array_first($itemsArray);
            $params['r_issuer'] = $this->refundService->getIssuer($refundItem['ti_id']);
            $refund = $this->refundService->create($params);
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
            'ti_xref_f_id' => 'required',
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

    /**
     * @param string $items
     * @param array  $request
     *
     * @return object
     */
    private function validateRefundRequest(string $items, array $request): object
    {
        return validator(array_merge(['items' => $items], $request), [
            'agent' => 'nullable',
            'reason' => 'nullable',
            'items' => [
                'bail',
                'required',
                'json',
                function ($attribute, $value, $fail) {
                    $array = json_decode($value, true);
                    $transactionItemsArray = [];
                    foreach ($array as $item) {
                        $item_array = (array) $item;
                        $transactionItemId = array_get($item_array, 'ti_id', '');
                        if (!is_int($transactionItemId)) {
                            $fail('The items.ti_id field must be an integer.');

                            break;
                        }
                        $amount = array_get($item_array, 'amount', '');
                        if (!is_numeric($amount)) {
                            $fail('The items.amount field should be numeric value');

                            break;
                        }
                        if ($amount < 0.00) {
                            $fail('The items.amount field should be more than 0.00');

                            break;
                        }
                        $quantity = array_get($item_array, 'quantity', '');
                        if ($quantity < 1) {
                            $fail('The items.quantity field should be more than 0');

                            break;
                        }
                        if (!is_int($quantity)) {
                            $fail('The items.quantity field should be integer');

                            break;
                        }
                        $transactionItemData = $this->transactionItemsService
                            ->fetch(['ti_id' => $transactionItemId])
                            ->first();
                        if (!empty($transactionItemData)) {
                            if ($quantity > $transactionItemData->ti_quantity) {
                                $fail('The items.quantity cannot be more than actual transaction item quantity.');
                            } else {
                                $checkIfRefundDone = $this->refundService->getRefundItemStatus($transactionItemId, $transactionItemData->ti_quantity, $quantity);
                                if (!$checkIfRefundDone) {
                                    $transactionItemsArray[] = $transactionItemId;
                                    $fail('The Refund request already done for item.ti_id '.implode(',', $transactionItemsArray));
                                }
                            }
                        } else {
                            $fail('The Refund request cannot be created for item.ti_id '.$transactionItemId);
                        }
                    }
                },
            ],
        ]);
    }
}
