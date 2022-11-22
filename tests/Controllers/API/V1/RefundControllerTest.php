<?php

namespace Tests\Controllers\API\V1;

use Throwable;

/**
 * @internal
 *
 * @covers \App\Http\Controllers\V1\RefundController
 * @covers \App\Models\Refund
 * @covers \App\Models\RefundItem
 * @covers \App\Models\RefundLog
 * @covers \App\Repositories\RefundItemRepository
 * @covers \App\Repositories\RefundLogRepository
 * @covers \App\Repositories\RefundRepository
 * @covers \App\Services\RefundService
 */
class RefundControllerTest extends TestCase
{
    /**
     * @var string
     */
    private $refundApi = 'api/v1/refund';

    /**
     * @var string
     */
    private $transactionItemsRefundsApi = 'api/v1/transaction_items_and_refunds';

    /**
     * @return void
     */
    public function testTrue(): void
    {
        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testTransactionItemsAndRefundsFieldValidation(): void
    {
        $this->get($this->transactionItemsRefundsApi.'/test');
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The ti xref f id must be an integer.',
            ]);
    }

    /**
     * @return void
     */
    public function testTransactionItemsAndRefundsNotFound(): void
    {
        $this->get($this->transactionItemsRefundsApi.'/111');
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'not found',
                'message' => 'Transaction not found or status is not done',
            ]);
    }

    /**
     * @throws Throwable
     *
     * @return void
     */
    public function testTransactionItemsAndRefundsSuccess(): void
    {
        $transactions = $this->generateTransaction([
            't_xref_fg_id' => 10000,
            't_transaction_id' => str_random(10),
            't_client' => 'be',
            't_issuer' => 'dzALG2be',
            't_gateway_transaction_id' => str_random(10),
            't_gateway' => 'cmi',
            't_currency' => 'MAD',
            't_status' => 'done',
            't_redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr',
            't_onerror_url' => 'onError_tlsweb_url?lang=fr-fr',
            't_reminder_url' => 'callback_to_send_reminder?lang=fr-fr',
            't_callback_url' => 'receipt_url/{fg_id}?lang=fr-fr',
            't_workflow' => 'vac',
            't_invoice_storage' => 'file-library',
        ]);

        $transactionItems = $this->generateTransactionItems($transactions->t_transaction_id);

        $refunds = $this->generateRefund();

        $this->generateRefundItems($refunds->r_id, $transactionItems->ti_id);

        $this->get($this->transactionItemsRefundsApi.'/10001');

        $this->response->assertStatus(200);

        $transactionsRefundList = $this->response->decodeResponseJson();
        $this->assertCount(1, $transactionsRefundList);
    }
}
