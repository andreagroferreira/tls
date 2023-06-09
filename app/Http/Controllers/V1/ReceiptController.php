<?php

namespace App\Http\Controllers\V1;

use App\Services\ReceiptService;
use Illuminate\Http\Request;

class ReceiptController extends BaseController
{
    protected $receiptService;

    public function __construct(ReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/generate_download_receipt",
     *     tags={"Payment API"},
     *     description="Generate receipt file for a transaction ID if not present. Return receipt file for a transaction ID if present.",
     *     @OA\Parameter(
     *          name="transaction_id",
     *          in="query",
     *          description="Transaction ID",
     *          required=true,
     *          @OA\Schema(type="string", example="visa-uk-dev-frPAR2uk-0000000001"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="The job has been added to the queue. This job will generate the receipt file and save it in file-library"
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     *      @OA\Response(
     *          response="404",
     *          description="Transaction does not exist"
     *      ),
     * )
     */
    public function generateOrDownloadReceipt(Request $request)
    {
        $validator = validator(
            ['transaction_id' => $request->input('transaction_id')],
            ['transaction_id' => 'required|string']
        );

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $fileName = 'receipt-' . $validator->validated()['transaction_id'] . '.pdf';
            $res = $this->receiptService->generateReceipt($validator->validated()['transaction_id'], $fileName);

            if (!$res) {
                return $this->sendResponse('Transaction does not exist', 404);
            }
            if ($res['type'] === 'download') {
                return $this->streamDownload(
                    function () use ($res) {
                        echo $res['fileContent'];
                    },
                    $fileName,
                    ['Content-type' => 'application/pdf']
                );
            } else if ($res['type'] === 'upload') {
                return $this->sendResponse('The job has been added to the queue. This job will generate the receipt file and save it in file-library');
            }
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }
}
