<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Support\Carbon;

/**
 * @internal
 *
 * @covers \App\Http\Controllers\V1\TransactionController
 * @covers \App\Models\TransactionItems
 * @covers \App\Models\Transactions
 * @covers \App\Repositories\TransactionItemsRepository
 * @covers \App\Repositories\TransactionRepository
 * @covers \App\Services\TransactionItemsService
 * @covers \App\Services\TransactionService
 */
class TransactionControllerTest extends TestCase
{
    /**
     * @var string
     */
    private $transactionApi = 'api/v1/transaction';

    /**
     *  @var string
     */
    private $transactionsApi = 'api/v1/transactions';

    /**
     * @return void
     */
    public function testTransactionApiMethodIsPost(): void
    {
        $this->get($this->transactionApi);
        $this->response->assertStatus(405);
    }

    /**
     * @return void
     */
    public function testGetTransactionApiParameterType(): void
    {
        $this->get($this->transactionApi.'/test');
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The fg id must be an integer.',
            ]);
    }

    /**
     * @return void
     */
    public function testGetTransactionThatDoesNotExists(): void
    {
        $this->get($this->transactionApi.'/1');
        $this->response->assertStatus(204);
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testGetSuccessfulTransactionFromDatabase(): void
    {
        $transaction = $this->generateTransaction();
        $this->generateTransactionItems($transaction->t_transaction_id);

        $this->get($this->transactionApi.'/'.$transaction->t_xref_fg_id);
        $this->response->assertStatus(200);

        $response_array = $this->response->decodeResponseJson();

        $this->assertNotEmpty($response_array);
        $this->assertNotEmpty(array_get($response_array, '0.items'));
    }

    /**
     * @dataProvider defaultPayload
     *
     * @param array $defaultPayload
     *
     * @return void
     */
    public function testCreateTransactionPrimaryFields(array $defaultPayload): void
    {
        $payload = [];
        foreach ($defaultPayload as $field => $value) {
            $this->post($this->transactionApi, $payload);
            $this->response->assertStatus(400)
                ->assertJson([
                    'error' => 'params error',
                    'message' => 'The '.str_replace('_', ' ', $field).' field is required.',
                ]);

            $payload[$field] = $value;
        }
    }

    /**
     * @dataProvider defaultPayload
     *
     * @param array $defaultPayload
     *
     * @return void
     */
    public function testCreateTransactionWithEmptyItems(array $defaultPayload): void
    {
        $defaultPayload['items'] = [];
        $this->post($this->transactionApi, $defaultPayload);
        $this->response->assertStatus(200);
    }

    /**
     * @dataProvider defaultPayload
     *
     * @param array $defaultPayload
     *
     * @return void
     */
    public function testCreateTransactionItemsFields(array $defaultPayload): void
    {
        // Validate missing items.skus
        $defaultPayload['items'] = [[]];
        $this->post($this->transactionApi, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.skus field is required.',
            ]);

        // Validate missing items.skus.sku, price and vat
        $defaultPayload['items'] = [['skus' => [[]]]];
        $this->post($this->transactionApi, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.skus.sku, price, vat field is required.',
            ]);

        // Validate missing items.fg_id
        $defaultPayload['items'] = [['skus' => [['sku' => 1, 'price' => 1, 'vat' => 1]]]];
        $this->post($this->transactionApi, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.f_id must be an integer.',
            ]);

        // Validate string items.fg_id
        $defaultPayload['items'] = [['skus' => [['sku' => 1, 'price' => 1, 'vat' => 1]], 'fg_id' => 'test']];
        $this->post($this->transactionApi, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.f_id must be an integer.',
            ]);
    }

    /**
     * @dataProvider defaultPayload
     *
     * @param array $defaultPayload
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function testCreateTransactionIsSuccessful(array $defaultPayload): void
    {
        // Create Transaction
        $this->post($this->transactionApi, $defaultPayload);
        $this->response->assertStatus(200)
            ->assertJsonStructure(['t_id', 'expire']);

        $postResponse = $this->response->decodeResponseJson();
        $this->assertTrue(Carbon::parse($this->getDbNowTime())->lt(array_get($postResponse, 'expire')));

        // Get Created Transaction
        $this->get($this->transactionApi.'/'.$defaultPayload['fg_id']);
        $this->response->assertStatus(200);

        $transactionData = $this->response->decodeResponseJson();
        $this->assertNotEmpty($transactionData);

        $this->assertEquals($postResponse['t_id'], array_get($transactionData, '0.t_id'));
        $this->assertEquals('pending', array_get($transactionData, '0.status'));
        $this->assertNotEmpty(array_get($transactionData, '0.items'));
    }

    /**
     * @dataProvider defaultPayload
     *
     * @param array $defaultPayload
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function testCreateTransactionWithGovernmentService(array $defaultPayload): void
    {
        // Create Transaction
        $defaultPayload['service'] = 'gov';
        $this->post($this->transactionApi, $defaultPayload);
        $this->response->assertStatus(200)
            ->assertJsonStructure(['t_id', 'expire']);

        $postResponse = $this->response->decodeResponseJson();
        $this->assertTrue(Carbon::parse($this->getDbNowTime())->lt(array_get($postResponse, 'expire')));

        // Get Created Transaction
        $this->get($this->transactionApi.'/'.$defaultPayload['fg_id']);
        $this->response->assertStatus(200);

        $transactionData = $this->response->decodeResponseJson();
        $this->assertNotEmpty($transactionData);

        $this->assertEquals('gov', array_get($transactionData, '0.service'));
    }

    /**
     * @dataProvider defaultPayload
     *
     * @param array $defaultPayload
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function testCreateMultipleTransactionsWithSameData(array $defaultPayload): void
    {
        // Create First Transaction
        $this->post($this->transactionApi, $defaultPayload);
        $this->response->assertStatus(200)
            ->assertJsonStructure(['t_id', 'expire']);

        $firstTransactionPost = $this->response->decodeResponseJson();

        // Create Second Transaction
        $this->post($this->transactionApi, $defaultPayload);
        $this->response->assertStatus(200)
            ->assertJsonStructure(['t_id', 'expire']);
        $secondTransactionPost = $this->response->decodeResponseJson();

        // Get Created Transaction
        $this->get($this->transactionApi.'/'.$defaultPayload['fg_id']);
        $this->response->assertStatus(200);

        $transactionData = $this->response->decodeResponseJson();

        $this->assertCount(1, $transactionData);
        $this->assertEquals(array_get($firstTransactionPost, 't_id'), array_get($secondTransactionPost, 't_id'));
        $this->assertEquals(1, array_get($transactionData, '0.t_id'));
    }

    /**
     * @dataProvider defaultPayload
     *
     * @param array $defaultPayload
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function testCreateMultipleTransactionsWithSameFormGroupAndDifferentItems(array $defaultPayload): void
    {
        // Create First Transaction
        $this->post($this->transactionApi, $defaultPayload);
        $this->response->assertStatus(200)
            ->assertJsonStructure(['t_id', 'expire']);

        $firstTransactionPost = $this->response->decodeResponseJson();

        // Create Second Transaction
        $defaultPayload['items'] = [['f_id' => 10001, 'skus' => [['sku' => 2, 'price' => 2, 'vat' => 2]]]];
        $this->post($this->transactionApi, $defaultPayload);
        $this->response->assertStatus(200)
            ->assertJsonStructure(['t_id', 'expire']);
        $secondTransactionPost = $this->response->decodeResponseJson();

        // Get Created Transaction
        $this->get($this->transactionApi.'/'.$defaultPayload['fg_id']);
        $this->response->assertStatus(200);

        $transactionData = $this->response->decodeResponseJson();

        $this->assertCount(1, $transactionData);
        $this->assertNotEquals(array_get($firstTransactionPost, 't_id'), array_get($secondTransactionPost, 't_id'));

        // Assert id of the transaction received is the second created
        $this->assertNotEquals(array_get($firstTransactionPost, 't_id'), array_get($transactionData, '0.t_id'));
        $this->assertEquals(array_get($secondTransactionPost, 't_id'), array_get($transactionData, '0.t_id'));
    }

    /**
     * @dataProvider defaultPayload
     *
     * @param array $defaultPayload
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function testTransactionExpired(array $defaultPayload): void
    {
        // Set expiration time negative.
        config(['payment_gateway.expiration_minutes' => -20]);

        $this->post($this->transactionApi, $defaultPayload);
        $this->response->assertStatus(200)
            ->assertJsonStructure(['t_id', 'expire']);

        $transactionPost = $this->response->decodeResponseJson();
        $this->assertEquals(Carbon::parse($this->getDbNowTime())->subMinutes(config('payment_gateway.expiration_minutes'))->toDateString(), Carbon::parse(array_get($transactionPost, 'expire'))->toDateString());

        $this->get($this->transactionApi.'/'.$defaultPayload['fg_id']);
        $this->response->assertStatus(204);
    }

    /**
     * @return void
     */
    public function testFetchAllWithTransactionsApiMethod(): void
    {
        $this->post($this->transactionsApi);
        $this->response->assertStatus(405);
    }

    /**
     * @return void
     */
    public function testFetchAllWithTransactionsResultStructure(): void
    {
        $this->get($this->transactionsApi);
        $this->response->assertStatus(200)
            ->assertJson([
                'total' => 0,
                'data' => [],
            ]);
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithTransactionsData(): void
    {
        $transaction = $this->generateTransaction();

        $this->get($this->transactionsApi);
        $this->response->assertStatus(200)
            ->assertJson([
                'total' => 1,
            ]);

        $transactionsList = $this->response->decodeResponseJson();
        $this->assertEquals($transaction->t_id, array_get($transactionsList, 'data.0.t_id'));
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithPagesFilterValidation(): void
    {
        $this->get($this->transactionsApi.'?page=test');
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The page must be an integer.',
            ]);
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithPageOneFilter(): void
    {
        $transaction = $this->generateTransaction();

        $this->get($this->transactionsApi.'?page=1');
        $this->response->assertStatus(200)
            ->assertJson([
                'total' => 1,
            ]);

        $transactionsList = $this->response->decodeResponseJson();
        $this->assertEquals($transaction->t_id, array_get($transactionsList, 'data.0.t_id'));
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithLimitFilterValidation(): void
    {
        $this->get($this->transactionsApi.'?limit=test');
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The limit must be an integer.',
            ]);
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithLimitFilter(): void
    {
        // Generate 2 transactions
        $this->generateTransaction();
        $this->generateTransaction([
            't_xref_fg_id' => 10001,
            't_transaction_id' => str_random(10),
            't_client' => 'be',
            't_issuer' => 'ruMOW2be',
            't_gateway_transaction_id' => str_random(10),
            't_gateway' => 'cmi',
            't_currency' => 'MAD',
            't_status' => 'pending',
            't_redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr',
            't_onerror_url' => 'onError_tlsweb_url?lang=fr-fr',
            't_reminder_url' => 'callback_to_send_reminder?lang=fr-fr',
            't_callback_url' => 'receipt_url/{fg_id}?lang=fr-fr',
            't_workflow' => 'vac',
        ]);

        $this->get($this->transactionsApi.'?limit=1');
        $this->response->assertStatus(200)
            ->assertJson([
                'total' => 2,
            ]);

        $transactionsList = $this->response->decodeResponseJson();
        $this->assertCount(1, $transactionsList['data']);
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithPageTwoFilter(): void
    {
        // Generate 2 transactions
        $this->generateTransaction();
        $this->generateTransaction([
            't_xref_fg_id' => 10001,
            't_transaction_id' => str_random(10),
            't_client' => 'be',
            't_issuer' => 'ruMOW2be',
            't_gateway_transaction_id' => str_random(10),
            't_gateway' => 'cmi',
            't_currency' => 'MAD',
            't_status' => 'pending',
            't_redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr',
            't_onerror_url' => 'onError_tlsweb_url?lang=fr-fr',
            't_reminder_url' => 'callback_to_send_reminder?lang=fr-fr',
            't_callback_url' => 'receipt_url/{fg_id}?lang=fr-fr',
            't_workflow' => 'vac',
        ]);

        $this->get($this->transactionsApi.'?page=2&limit=1');
        $this->response->assertStatus(200)
            ->assertJson([
                'total' => 2,
            ]);

        $transactionsList = $this->response->decodeResponseJson();
        $this->assertCount(1, $transactionsList['data']);
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithIssuerFilterValidation(): void
    {
        $this->get($this->transactionsApi.'?issuer=test');
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The issuer format is invalid.',
            ]);
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithIssuerFilter(): void
    {
        $transaction = $this->generateTransaction();

        $this->get($this->transactionsApi.'?issuer=dzALG2be');
        $this->response->assertStatus(200)
            ->assertJson([
                'total' => 1,
            ]);

        $transactionsList = $this->response->decodeResponseJson();
        $this->assertEquals($transaction->t_id, array_get($transactionsList, 'data.0.t_id'));
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithStatusFilterValidation(): void
    {
        $this->get($this->transactionsApi.'?status=test');
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The selected status is invalid.',
            ]);
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithStatusFilter(): void
    {
        $transaction = $this->generateTransaction();

        $this->get($this->transactionsApi.'?status=pending');
        $this->response->assertStatus(200)
            ->assertJson([
                'total' => 1,
            ]);

        $transactionsList = $this->response->decodeResponseJson();
        $this->assertEquals($transaction->t_id, array_get($transactionsList, 'data.0.t_id'));
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithStartDateFilterValidation(): void
    {
        $this->get($this->transactionsApi.'?start_date=2021-01-32');
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The start date is not a valid date.',
            ]);
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithEndDateFilterValidation(): void
    {
        $this->get($this->transactionsApi.'?start_date=2021-01-01&end_date=2021-12-32');
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The end date is not a valid date.',
            ]);
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithDatesFilter(): void
    {
        $transaction = $this->generateTransaction();

        $yesterday = Carbon::today()->subDay(1);
        $tomorrow = Carbon::today()->addDay(1);
        $this->get($this->transactionsApi.'?start_date='.$yesterday->toDateString().'&end_date='.$tomorrow->toDateString());
        $this->response->assertStatus(200)
            ->assertJson([
                'total' => 1,
            ]);

        $transactionsList = $this->response->decodeResponseJson();
        $this->assertEquals($transaction->t_id, array_get($transactionsList, 'data.0.t_id'));
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithDatesFilterWithNoResult(): void
    {
        $this->generateTransaction();

        $tomorrow = Carbon::today()->addDay();
        $this->get($this->transactionsApi.'?start_date='.$tomorrow->toDateString().'&end_date='.$tomorrow->toDateString());
        $this->response->assertStatus(200)
            ->assertJson([
                'total' => 0,
            ]);
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithServiceFilterValidation(): void
    {
        $this->get($this->transactionsApi.'?service=test');
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The selected service is invalid.',
            ]);
    }

    /**
     * @throws \Throwable
     *
     * @return void
     */
    public function testFetchAllWithServiceFilter(): void
    {
        // Generate 2 transactions
        $this->generateTransaction();
        $this->generateTransaction([
            't_xref_fg_id' => 10001,
            't_transaction_id' => str_random(10),
            't_client' => 'be',
            't_issuer' => 'ruMOW2be',
            't_gateway_transaction_id' => str_random(10),
            't_gateway' => 'cmi',
            't_currency' => 'MAD',
            't_status' => 'pending',
            't_service' => 'gov',
            't_redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr',
            't_onerror_url' => 'onError_tlsweb_url?lang=fr-fr',
            't_reminder_url' => 'callback_to_send_reminder?lang=fr-fr',
            't_callback_url' => 'receipt_url/{fg_id}?lang=fr-fr',
            't_workflow' => 'vac',
        ]);

        $this->get($this->transactionsApi.'?service=gov');
        $this->response->assertStatus(200)
            ->assertJson([
                'total' => 1,
            ]);

        $transactionsList = $this->response->decodeResponseJson();

        $this->assertCount(1, $transactionsList['data']);
    }

    public function defaultPayload(): array
    {
        return [
            [
                [
                    'fg_id' => 10001,
                    'client' => 'be',
                    'issuer' => 'dzALG2fr',
                    'currency' => 'EUR',
                    'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr',
                    'onerror_url' => 'onError_tlsweb_url?lang=fr-fr',
                    'reminder_url' => 'callback_to_send_reminder?lang=fr-fr',
                    'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr',
                    'workflow' => 'vac',
                    'items' => [
                        [
                            'skus' => [
                                [
                                    'sku' => 1,
                                    'price' => 1,
                                    'vat' => 1,
                                ],
                            ],
                            'f_id' => 10001,
                        ],
                    ],
                ],
            ],
        ];
    }
}
