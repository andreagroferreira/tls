<?php

namespace App\Http\Controllers\API\V1;

use App\Services\InvoiceService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends BaseController
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/invoice/{transaction_id}",
     *     tags={"Payment API"},
     *     description="download the invoice file according to transaction_id",
     *      @OA\Parameter(
     *          name="transaction_id",
     *          in="path",
     *          description="the transaction_id",
     *          required=true,
     *          @OA\Schema(type="string", example="DEVELOPMENT20210414-dzALG2be-0000000055"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="return invoice pdf",
     *          @OA\MediaType(
    mediaType="application/pdf"
     *          ),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     *      @OA\Response(
     *          response="404",
     *          description="not found"
     *      ),
     * )
     */
    public function fetch($transaction_id)
    {
        $validator = validator(['transaction_id' => $transaction_id], [
            'transaction_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $res = $this->invoiceService->getInvoiceFileContent($transaction_id);

            if ($res) {
                return $this->streamDownload(function () use ($res) {
                    echo $res;
                },
                    $transaction_id . '.pdf',
                    ['Content-type' => 'application/pdf']
                );
            } else {
                return $this->sendEmptyResponse(404);
            }
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }

    }
}
