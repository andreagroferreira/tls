<?php

namespace App\Services\PaymentGateways;

use App\Contracts\Services\PaymentGatewayServiceInterface;
use App\PaymentGateway\V2\Gateways\EasypayPaymentGateway;
use App\Services\PaymentService;
use App\Services\TransactionLogsService;
use App\Services\V2\TransactionItemService;
use App\Services\V2\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class Easypay implements PaymentGatewayServiceInterface
{
    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var TransactionLogsService
     */
    protected $transactionLogsService;

    /**
     * @var EasypayPaymentGateway
     */
    protected $gateway;

    /**
     * @var PaymentService
     */
    protected $paymentService;

    public function __construct(
        EasypayPaymentGateway $gateway,
        TransactionService $transactionService,
        TransactionLogsService $transactionLogsService,
        PaymentService $paymentService
    ) {
        $this->transactionService = $transactionService;
        $this->transactionLogsService = $transactionLogsService;
        $this->gateway = $gateway;
        $this->paymentService = $paymentService;
    }

    /**
     * Handles the payment request.
     *
     * [redirTo]
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
            if ($transaction = $this->transactionService->get($request->t_id)) {
                $this->transactionLogsService->create([
                    'tl_xref_transaction_id' => $transaction['t_transaction_id'],
                    'tl_content' => json_encode($request->all()),
                ]);
            }

            if ($transaction === null) {
                throw new \Exception('Transaction not found for id: ' . $request->t_id);
            }

            if ($transaction->t_status !== 'pending') {
                throw new \Exception('Transaction ' . $request->t_id . ' is not pending');
            }

            $transactionItemsService = new TransactionItemService($transaction->t_transaction_id);

            $transaction->update(['t_gateway' => 'easypay', 't_xref_pa_id' => (int) $request->pa_id]);

            $payment = $this->gateway->charge($transactionItemsService->getAmount(), [
                'transaction' => $transaction,
                'items' => $transactionItemsService->getItems(),
            ]);
        } catch (\Exception $e) {
            Log::error('[Services\PaymentGateways\Easypay] - General Payment Controller Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $payment;
    }

    /**
     * Receives a request from the payment gateway and manages a transaction based on the request.
     *
     * [notify]
     *
     * @param Request $request
     *
     * @return array
     */
    public function callback(Request $request)
    {
        $transaction = $this->transactionService->getByTransactionId($request->order_id);
        if ($request->action !== 'payment') {
            return [
                'is_success' => 'fail',
                'message' => '[Services\PaymentGateways\Easypay] - Invalid callback action',
            ];
        }

        try {
            if ($transaction === null) {
                return [
                    'is_success' => 'fail',
                    'message' => 'Transaction not found for id: ' . $request->order_id,
                ];
            }

            if ($transaction->t_status === 'done') {
                return [
                    'is_success' => 'ok',
                    'message' => 'Transaction OK: transaction has been confirmed',
                    'orderid' => $transaction->t_transaction_id,
                    'href' => $transaction->t_redirect_url,
                ];
            }

            $transactionItemsService = new TransactionItemService($transaction->t_transaction_id);

            return $this->gateway->callback(
                $transaction,
                $transactionItemsService->getItemsPreparedToSync(),
                $transactionItemsService->getAmount(),
                $request
            );
        } catch (\Exception $e) {
            Log::error('[Services\PaymentGateways\Easypay] - General Payment Controller Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'is_success' => 'fail',
                'message' => $e->getMessage(),
                'href' => $transaction->t_onerror_url,
            ];
        }
    }

    /**
     * Receives a request from the payment gateway and returns the status of the transation to the UI.
     *
     * [return]
     *
     * @param Request $request
     *
     * @return array
     */
    public function validateTransactionStatus(Request $request)
    {
        $error = $request->get('error');
        if ($error !== null) {
            return [
                'is_success' => 'fail',
                'message' => 'Payment Error: ' . $error['errorMessage'],
            ];
        }

        $transaction = $this->transactionService->getByTransactionId($request->orderId);

        if ($transaction === null) {
            return [
                'is_success' => 'fail',
                'message' => 'Transaction not found for id: ' . $request->orderId,
            ];
        }

        $returnParams = [
            'is_success' => 'ok',
            'message' => 'Transaction OK',
            'orderid' => $transaction->t_transaction_id,
            'href' => $transaction->t_redirect_url,
        ];

        if ($transaction->t_status !== 'done') {
            $orderStatus = $this->gateway->checkOrderStatus($transaction);

            switch ($orderStatus) {
                case 'accepted':
                    $transactionItemsService = new TransactionItemService($transaction->t_transaction_id);

                    $transactionData = array_merge($transaction->toArray(), [
                        't_items' => $transactionItemsService->getItemsPreparedToSync(),
                        't_amount' => $transactionItemsService->getAmount(),
                    ]);

                    return $this->paymentService->confirm($transactionData, [
                        'gateway' => $transaction->t_gateway,
                        'amount' => $request->amount,
                        'currency' => $transaction->t_currency,
                        'gateway_transaction_id' => $request->transactionId,
                        'gateway_transaction_reference' => $request->transactionId,
                    ]);

                case 'pending':
                    $returnParams['is_success'] = 'ok';
                    $returnParams['message'] = 'Transaction PENDING: transaction is being processed, but not yet confirmed, please wait';
                    $returnParams['href'] = $transaction->t_redirect_url;

                    break;

                case 'declined':
                    $returnParams['is_success'] = 'fail';
                    $returnParams['message'] = 'Transaction DECLINED';
                    $returnParams['href'] = $transaction->t_onerror_url;

                    break;

                default:
                    $returnParams['is_success'] = 'fail';
                    $returnParams['message'] = 'Unknown error: an unexpected error occurred, please try again';
                    $returnParams['href'] = $transaction->t_onerror_url;

                    Log::error('[Services\PaymentGateways\Easypay] - Unexpected error occurred while checking the order status.', [
                        'transaction' => $transaction,
                        'orderStatus' => $orderStatus,
                    ]);

                    break;
            }
        }

        return $returnParams;
    }

    /**
     * Validates the request data based on the rules.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isValid(Request $request): bool
    {
        return Validator::make($request->all(), [
            't_id' => 'required|int',
            'pa_id' => 'required|int',
            'lang' => 'nullable|string',
        ])->passes();
    }
}
