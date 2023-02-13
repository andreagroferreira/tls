<?php

namespace App\Services\PaymentGateways;

use App\Contracts\Services\PaymentGatewayServiceInterface;
use App\PaymentGateway\V2\Gateways\EasypayPaymentGateway;
use App\Services\V2\TransactionItemService;
use App\Services\V2\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Easypay implements PaymentGatewayServiceInterface
{
    /**
     * Handles the payment request.
     *
     * @param Request $request
     *
     * @return ?array
     */
    public function handle(Request $request): ?array
    {
        if (!$this->isValid($request)) {
            return null;
        }

        try {
            $transaction = TransactionService::get($request->t_id);
            $items = TransactionItemService::getAllByTransactionId($transaction->t_transaction_id);
            (new EasypayPaymentGateway())->charge(1043.23, [
                'transaction_id' => $transaction->t_transaction_id,
                'items' => $items,
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    public function notify()
    {
        // ...
    }

    public function return()
    {
        // ...
    }

    /**
     * Validates the request data based on the rules.
     *
     * @param Request $request
     *
     * @return bool
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function isValid(Request $request): bool
    {
        return Validator::make($request->all(), [
            't_id' => 'required|int',
        ])->passes();
    }
}
