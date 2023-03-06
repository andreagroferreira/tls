<?php

namespace App\Services\PaymentGateways;

use App\Contracts\Services\PaymentGatewayServiceInterface;
use App\PaymentGateway\V2\Gateways\EasypayPaymentGateway;
use App\Services\V2\TransactionItemService;
use App\Services\V2\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class Easypay implements PaymentGatewayServiceInterface
{
    /**
     * Handles the payment request.
     *
     * @param Request $request
     *
     * @return array|false
     */
    public function handle(Request $request)
    {
        if (!$this->isValid($request)) {
            return false;
        }

        $payment = [];

        try {
            $transaction = (new TransactionService)->get($request->t_id);
            $transactionItemsService = new TransactionItemService($transaction->t_transaction_id);

            $payment = (new EasypayPaymentGateway)->charge($transactionItemsService->getAmount(), [
                'transaction_id' => $transaction->t_transaction_id,
                'items' => $transactionItemsService->getItems(),
            ]);
        } catch (Exception $e) {
            Log::error('[Services\PaymentGateways\Easypay] - General Payment Controller Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $payment;
    }

    public function callback()
    {
        /**
         * Get transaction and validate status
         *
         * If it is done return
         * If it is closed or pending
         *     Verify the sign
         *     Check if payment status is success
         *     If so, call PaymentService::confirm()
         *
         * Needs to return
         * [
            'status' => 'success',
            'orderId' => $orderId,
            'message' => 'The transaction paid successfully.',
            'href' => $transaction['t_redirect_url'],
            ]
         */
        return '/v2/return';
    }

    /**
     * Validates the request data based on the rules.
     *
     * @param Request $request
     *
     * @return bool
     *
     */
    private function isValid(Request $request): bool
    {
        return Validator::make($request->all(), [
            't_id' => 'required|int',
//            'pa_id' => 'required|int',
//            'lang' => 'required|string',
        ])->passes();
    }
}
