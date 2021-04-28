<?php

namespace App\Http\Controllers\API\V1;

use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends BaseController
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/transaction/{fg_id}",
     *     tags={"Payment API"},
     *     description="get the transaction details according to fg_id",
     *      @OA\Parameter(
     *          name="fg_id",
     *          in="path",
     *          description="the tlsconnect fg_id",
     *          required=true,
     *          @OA\Schema(type="integer", example="10000"),
     *      ),
     *     @OA\Parameter(
     *          name="order",
     *          in="query",
     *          description="desc or asc",
     *          required=false,
     *          @OA\Schema(type="string", example=""),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="get the transaction information",
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
            'fg_id' => $request->route('fg_id'),
            'order' => $request->input('order', 'desc')
        ];
        $validator = validator($params, [
            'fg_id' => 'required|integer',
            'order' => [
                'required',
                'string',
                Rule::in(['desc', 'asc']),
            ]
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $res = $this->transactionService->fetch($validator->validated());
            if ($res) {
                return $this->sendResponse($res);
            } else {
                return $this->sendEmptyResponse(404);
            }
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/transaction",
     *     tags={"Payment API"},
     *     description="create a new transaction",
     *      @OA\Parameter(
     *          name="fg_id",
     *          in="query",
     *          description="tlsconnect fg_id",
     *          required=true,
     *          @OA\Schema(type="integer", example="10000"),
     *      ),
     *      @OA\Parameter(
     *          name="client",
     *          in="query",
     *          description="the client id",
     *          required=true,
     *          @OA\Schema(type="string", example="be"),
     *      ),
     *     @OA\Parameter(
     *          name="issuer",
     *          in="query",
     *          description="the issuer tab",
     *          required=true,
     *          @OA\Schema(type="string", example="dzALG2be"),
     *      ),
     *     @OA\Parameter(
     *          name="currency",
     *          in="query",
     *          description="the payment currency for this transaction",
     *          required=true,
     *          @OA\Schema(type="string", example="MAD"),
     *      ),
     *     @OA\Parameter(
     *          name="redirect_url",
     *          in="query",
     *          description="the tlsconnect website redirection url",
     *          required=true,
     *          @OA\Schema(type="string", example="onSuccess_tlsweb_url?lang=fr-fr"),
     *      ),
     *     @OA\Parameter(
     *          name="onerror_url",
     *          in="query",
     *          description="the tlsconnect website error url",
     *          required=true,
     *          @OA\Schema(type="string", example="onError_tlsweb_url?lang=fr-fr"),
     *      ),
     *     @OA\Parameter(
     *          name="reminder_url",
     *          in="query",
     *          description="the tlsconnect website reminder url",
     *          required=true,
     *          @OA\Schema(type="string", example="callback_to_send_reminder?lang=fr-fr"),
     *      ),
     *     @OA\Parameter(
     *          name="callback_url",
     *          in="query",
     *          description="the tlsconnect website callback url",
     *          required=true,
     *          @OA\Schema(type="string", example="receipt_url/{fg_id}?lang=fr-fr"),
     *      ),
     *     @OA\Parameter(
     *          name="workflow",
     *          in="query",
     *          description="workflow",
     *          required=true,
     *          @OA\Schema(type="string", example="postal"),
     *      ),
     *     @OA\Parameter(
     *          name="items",
     *          in="query",
     *          description="transaction items. ",
     *          required=true,
     *          @OA\Schema(type="string", example=""),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="transaction created",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function create(Request $request)
    {
        $validator = validator($request->all(), [
            'fg_id' => 'required|integer',
            'client' => 'required|string',
            'issuer' => 'required|string',
            'currency' => 'required|string',
            'redirect_url' => 'required|string',
            'onerror_url' => 'required|string',
            'reminder_url' => 'required|string',
            'callback_url' => 'required|string',
            'workflow' => 'required|string',
            'items' => [
                'bail',
                'required',
                'json',
                function ($attribute, $value, $fail) {
                    $array = json_decode($value, true);
                    foreach ($array as $item) {
                        $item_array = (array)$item;
                        $f_id = array_get($item_array, 'f_id', '');
                        if (!is_numeric($f_id)) {
                            $fail('The items.f_id must be an integer.');
                        }
                        $skus = array_get($item_array, 'skus', []);
                        if (blank($skus)) {
                            $fail('The items.skus field is required.');
                        }
                        foreach ($skus as $sku) {
                            $diff = array_diff(['sku', 'price', 'vat'], array_keys((array)$sku));
                            if (filled($diff)) {
                                $fail('The items.skus.' . implode(', ', $diff) . ' field is required.');
                            }
                        }
                    }
                }
            ],
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $params = $validator->validated();
            $transaction = $this->transactionService->checkDuplicateCreation($params);
            if (is_array($transaction)) {
                return $this->sendResponse($transaction);
            }

            $res = $this->transactionService->create($params);

            if ($res) {
                return $this->sendResponse($res);
            } else {
                return $this->sendError('unknown_error', 'create failed');
            }
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }
}
