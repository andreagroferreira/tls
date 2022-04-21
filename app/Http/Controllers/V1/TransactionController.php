<?php

namespace App\Http\Controllers\V1;

use App\Services\TransactionService;
use Carbon\Carbon;
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
                return $this->sendEmptyResponse(204);
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
     *          name="payment_method",
     *          in="query",
     *          description="tls agent payment method",
     *          required=false,
     *          @OA\Schema(type="string", example="card"),
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
        $items = is_array($request->input('items')) ? json_encode($request->input('items')) : $request->input('items');
        $validator = validator(array_merge(['items' => $items], $request->except('items')), [
            'fg_id' => 'required|integer',
            'client' => 'required|string',
            'issuer' => 'required|string',
            'currency' => 'required|string',
            'redirect_url' => 'required|string',
            'onerror_url' => 'required|string',
            'reminder_url' => 'required|string',
            'callback_url' => 'required|string',
            'workflow' => 'required|string',
            'payment_method' => 'nullable',
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

    /**
     * @OA\Get(
     *     path="/api/v1/transactions",
     *     tags={"Payment API"},
     *     description="get all transactions",
     *     @OA\Parameter(
     *          name="page",
     *          in="query",
     *          description="page, default 1",
     *          required=false,
     *          @OA\Schema(type="integer", example="1"),
     *      ),
     *     @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          description="number of result per page",
     *          required=false,
     *          @OA\Schema(type="integer", example="20"),
     *      ),
     *     @OA\Parameter(
     *          name="issuer",
     *          in="query",
     *          description="define which issuer you want to fetch",
     *          required=false,
     *          @OA\Schema(type="string", example="egCAI2be,egCAI2be"),
     *      ),
     *     @OA\Parameter(
     *          name="start_date",
     *          in="query",
     *          description="start date",
     *          required=false,
     *          @OA\Schema(type="date", example="2021-01-01"),
     *      ),
     *      @OA\Parameter(
     *          name="end_date",
     *          in="query",
     *          description="end date",
     *          required=false,
     *          @OA\Schema(type="date", example="2021-12-31"),
     *      ),
     *      @OA\Parameter(
     *          name="status",
     *          in="query",
     *          description="transaction stats, eg pending, waiting, close, done",
     *          required=false,
     *          @OA\Schema(type="string", example="pending"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="get the transaction",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function fetchAll(Request $request)
    {
        $params = [
            'page' => $request->input('page', 1),
            'limit' => $request->input('limit', 20),
            'start_date' => $request->input('start_date', Carbon::today()->toDateString()),
            'end_date' => $request->input('end_date', Carbon::tomorrow()->toDateString())
        ];

        if ($request->has('issuer')) {
            $params['issuer'] = explode(',', trim($request->input('issuer'), ','));
        }

        $validator = validator(array_merge($params, $request->only(['status'])), [
            'page' => 'required|integer',
            'limit' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'issuer.*' => 'sometimes|required|regex:/^[a-zA-Z]{5}2[a-zA-Z]{2}$/',
            'status' => [
                'sometimes',
                'required',
                Rule::in(['pending', 'waiting', 'close', 'done'])
            ]
        ], [
            'issuer.*.regex' => 'The issuer format is invalid.'
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $res = $this->transactionService->fetchAll($validator->validated());

            return $this->sendResponse($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());

        }
    }

    /**
     * @OA\Post(
     *     path="/resend_failed_transaction",
     *     tags={"Payment API"},
     *     description="resend transaction",
     *     @OA\Parameter(
     *          name="queue_name",
     *          in="query",
     *          description="queue name",
     *          required=true,
     *          @OA\Schema(type="string", example="tlscontact_transaction_sync_queue"),
     *      ),
     *     @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="resend transaction failed_job_id",
     *          required=false,
     *          @OA\Schema(type="int"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="added to transaction queue",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request, added to transaction queue failed"
     *      ),
     * )
     */

    public function resend(Request $request)
    {
        $params = [
            'id' => $request->input('id'),
            'queue_name' => $request->input('queue_name')
        ];
        $validator = validator($params, [
            'queue_name' => 'required|string',
            'id' => 'nullable'
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }
        $params = $validator->validated();
        try {
            $result = $this->transactionService->resend($params);
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
     *     path="/fetchJob",
     *     tags={"Email Service"},
     *     description="get jobs volume",
     *     @OA\Parameter(
     *          name="queue_name",
     *          in="query",
     *          description="queue name",
     *          required=true,
     *          @OA\Schema(type="string", example="tlscontact_transaction_sync_queue"),
     *      ),
     *     @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="resend transaction job_id",
     *          required=false,
     *          @OA\Schema(type="int"),
     *      ),
     *     @OA\Response(
     *         response="200",
     *         description="return the volume count",
     *         @OA\JsonContent(),
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: bad request, get volume failed"
     *     ),
     * )
     */
    public function fetchJob(Request $request)
    {
        $params = [
            'id' => $request->input('id'),
            'queue_name' => $request->input('queue_name')
        ];
        $validator = validator($params, [
            'queue_name' => 'required|string',
            'id' => 'nullable'
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }
        $params = $validator->validated();
        try {
            $result = $this->transactionService->fetchJob($params);
            return $this->sendResponse($result);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/fetchFailJob",
     *     tags={"Email Service"},
     *     description="get failed_jobs volume",
     *     @OA\Parameter(
     *          name="queue_name",
     *          in="query",
     *          description="queue name",
     *          required=true,
     *          @OA\Schema(type="string", example="tlscontact_transaction_sync_queue"),
     *      ),
     *     @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="resend transaction failed_job_id",
     *          required=false,
     *          @OA\Schema(type="int"),
     *      ),
     *     @OA\Response(
     *         response="200",
     *         description="return the volume count",
     *         @OA\JsonContent(),
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error: bad request, get volume failed"
     *     ),
     * )
     */
    public function fetchFailJob(Request $request)
    {
        $params = [
            'id' => $request->input('id'),
            'queue_name' => $request->input('queue_name')
        ];
        $validator = validator($params, [
            'queue_name' => 'required|string',
            'id' => 'nullable'
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }
        $params = $validator->validated();
        try {
            $result = $this->transactionService->fetchFailJob($params);
            return $this->sendResponse($result);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

}
