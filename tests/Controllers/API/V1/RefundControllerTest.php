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
 * @covers \App\Repositories\TransactionItemsRepository
 * @covers \App\Services\RefundService
 * @covers \App\Services\TransactionItemsService
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

    /**
     * @dataProvider defaultPayload
     *
     * @param array $defaultPayload
     *
     * @return void
     */
    public function testCreateRefundItemsFields(array $defaultPayload): void
    {
        // Validate items.ti_id
        $defaultPayload['items'] = [['ti_id' => 'test', 'amount' => 454, 'quantity' => 1, 'status' => 'pending']];
        $this->post($this->refundApi, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.ti_id field must be an integer.',
            ]);

        // Validate items.amount is number
        $defaultPayload['items'] = [['ti_id' => 1, 'amount' => 'dff', 'quantity' => 1, 'status' => 'pending']];
        $this->post($this->refundApi, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.amount field should be numeric value',
            ]);

        // Validate items.amount greater than 0.0
        $defaultPayload['items'] = [['ti_id' => 1, 'amount' => -34, 'quantity' => 1, 'status' => 'pending']];
        $this->post($this->refundApi, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.amount field should be more than 0.00',
            ]);
    }

    /**
     * @dataProvider defaultPayload
     *
     * @param array $defaultPayload
     *
     * @return void
     */
    public function testCreateRefundQuantityField(array $defaultPayload): void
    {
        // Validate items.quantity greater than 0
        $defaultPayload['items'] = [['ti_id' => 1, 'amount' => 454, 'quantity' => -1, 'status' => 'pending']];
        $this->post($this->refundApi, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.quantity field should be more than 0',
            ]);

        // Validate items.quantity is integer
        $defaultPayload['items'] = [['ti_id' => 1, 'amount' => 454, 'quantity' => 1.5, 'status' => 'pending']];
        $this->post($this->refundApi, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.quantity field should be integer',
            ]);

        // Validate items.quantity is not greater than transaction item quantity
        $transactions = $this->generateTransaction();

        $transactionItems = $this->generateTransactionItems($transactions->t_transaction_id);

        $defaultPayload['items'] = [['ti_id' => $transactionItems->ti_id, 'amount' => 454, 'quantity' => $transactionItems->ti_quantity + 1, 'status' => 'pending']];
        $this->post($this->refundApi, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.quantity cannot be more than actual transaction item quantity.',
            ]);
    }

    /**
     * @dataProvider defaultPayload
     *
     * @param array $defaultPayload
     *
     * @return void
     */
    public function testCreateRefundItemsAlreadyExist(array $defaultPayload): void
    {
        $transactions = $this->generateTransaction();

        $transactionItems = $this->generateTransactionItems($transactions->t_transaction_id);

        $refunds = $this->generateRefund();

        $refundItems = $this->generateRefundItems($refunds->r_id, $transactionItems->ti_id);
        $defaultPayload['items'] = [['ti_id' => $refundItems->ri_xref_ti_id, 'amount' => 454, 'quantity' => 1, 'status' => 'pending']];
        $this->post($this->refundApi, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The Refund request already done for item.ti_id '.$refundItems->ri_xref_ti_id,
            ]);
    }

    /**
     * @dataProvider defaultPayload
     *
     * @param array $defaultPayload
     *
     * @return void
     */
    public function testCreateRefundTransactionItemsExist(array $defaultPayload): void
    {
        $defaultPayload['items'] = [['ti_id' => 0, 'amount' => 454, 'quantity' => 1, 'status' => 'pending']];
        $this->post($this->refundApi, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The Refund request cannot be created for item.ti_id 0',
            ]);
    }

    /**
     * @dataProvider defaultPayload
     *
     * @param array $defaultPayload
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testCreateRefundIsSuccessful(array $defaultPayload): void
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

        $defaultPayload['items'] = [['ti_id' => $transactionItems->ti_id, 'amount' => 454, 'quantity' => 1, 'status' => 'pending']];
        $this->post($this->refundApi, $defaultPayload);
        $this->response->assertStatus(200)
            ->assertJsonStructure(['r_id']);
        $postResponse = $this->response->decodeResponseJson();

        // Get Created Refund
        $this->get($this->transactionItemsRefundsApi.'/'.$transactionItems->ti_xref_f_id);
        $this->response->assertStatus(200);

        $transactionRefundItemsData = $this->response->decodeResponseJson();
        $this->assertNotEmpty($transactionRefundItemsData);

        $this->assertEquals($postResponse['r_id'], array_get($transactionRefundItemsData, '0.refund_request.r_id'));
        $this->assertNotEmpty(array_get($transactionRefundItemsData, '0.items.skus.0.refund_items'));
    }

    /**
     * @return void
     */
    public function testGetRefundRequestMethod(): void
    {
        $this->post($this->refundApi.'/111');
        $this->response->assertStatus(405);
    }

    /**
     * @return void
     */
    public function testGetRefundRequestValidation(): void
    {
        $this->get($this->refundApi.'/test');
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The r id must be an integer.',
            ]);
    }

    /**
     * @return void
     */
    public function testGetRefundRequestNotFound(): void
    {
        $this->get($this->refundApi.'/111');
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'not found',
                'message' => 'Refund request not found',
            ]);
    }

    /**
     * @throws Throwable
     *
     * @return void
     */
    public function testGetRefundRequestSuccess(): void
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

        $this->get($this->refundApi.'/1');
        $this->response->assertStatus(200);
    }

    public function defaultPayload(): array
    {
        return [
            [
                [
                    'agent' => 'test@test.com',
                    'reason' => 'other',
                    'appointment_date' => '2022-11-30 00:00:00',
                    'items' => [
                        [
                            'ti_id' => 1,
                            'amount' => 454,
                            'quantity' => 1,
                            'status' => 'pending',
                        ],
                    ],
                ],
            ],
        ];
    }
}
