<?php

namespace Tests\Controllers\API\V1;

use App\Models\Transactions;
use Illuminate\Support\Carbon;

class TransactionControllerTest extends TestCase
{
    /**
     * @var string
     */
    private string $baseApiUrl = 'api/v1/transaction';

    /**
     * @return void
     */
    public function testTransactionApiMethodIsPost(): void
    {
        $this->get($this->baseApiUrl);
        $this->response->assertStatus(405);
    }

    /**
     * @return void
     */
    public function testGetTransactionApiParameterType(): void
    {
        $this->get($this->baseApiUrl . '/test');
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
        $this->get($this->baseApiUrl . '/1');
        $this->response->assertStatus(204);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testGetSuccessfulTransactionFromDatabase(): void
    {
        $transaction = $this->generateTransaction();
        $this->generateTransactionItems($transaction->t_transaction_id);

        $this->get($this->baseApiUrl . '/' . $transaction->t_xref_fg_id);
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
            $this->post($this->baseApiUrl, $payload);
            $this->response->assertStatus(400)
                ->assertJson([
                    'error' => 'params error',
                    'message' => 'The ' . str_replace('_', ' ', $field) . ' field is required.',
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
        $this->post($this->baseApiUrl, $defaultPayload);
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
        //Validate missing items.skus
        $defaultPayload['items'] = [[]];
        $this->post($this->baseApiUrl, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.skus field is required.',
            ]);

        //Validate missing items.skus.sku, price and vat
        $defaultPayload['items'] = [['skus' => [[]]]];
        $this->post($this->baseApiUrl, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.skus.sku, price, vat field is required.',
            ]);

        //Validate missing items.fg_id
        $defaultPayload['items'] = [['skus' => [['sku' => 1, 'price' => 1, 'vat' => 1]]]];
        $this->post($this->baseApiUrl, $defaultPayload);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.f_id must be an integer.',
            ]);

        //Validate string items.fg_id
        $defaultPayload['items'] = [['skus' => [['sku' => 1, 'price' => 1, 'vat' => 1]], 'fg_id' => 'test']];
        $this->post($this->baseApiUrl, $defaultPayload);
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
     * @return void
     *
     * @throws \Throwable
     */
    public function testCreateTransactionIsSuccessful(array $defaultPayload): void
    {
        //Create Transaction
        $this->post($this->baseApiUrl, $defaultPayload);
        $this->response->assertStatus(200)
            ->assertJsonStructure(['t_id', 'expire']);

        $postResponse = $this->response->decodeResponseJson();
        $this->assertTrue(Carbon::parse($this->getDbNowTime())->lt(array_get($postResponse, 'expire')));

        //Get Created Transaction
        $this->get($this->baseApiUrl . '/' . $defaultPayload['fg_id']);
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
     * @return void
     *
     * @throws \Throwable
     */
    public function testCreateMultipleTransactionsWithSameData(array $defaultPayload): void
    {
        //Create First Transaction
        $this->post($this->baseApiUrl, $defaultPayload);
        $this->response->assertStatus(200)
            ->assertJsonStructure(['t_id', 'expire']);

        $firstTransactionPost = $this->response->decodeResponseJson();

        //Create Second Transaction
        $this->post($this->baseApiUrl, $defaultPayload);
        $this->response->assertStatus(200)
            ->assertJsonStructure(['t_id', 'expire']);
        $secondTransactionPost = $this->response->decodeResponseJson();

        //Get Created Transaction
        $this->get($this->baseApiUrl . '/' . $defaultPayload['fg_id']);
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
     * @return void
     *
     * @throws \Throwable
     */
    public function testCreateMultipleTransactionsWithSameFormGroupAndDifferentItems(array $defaultPayload): void
    {
        //Create First Transaction
        $this->post($this->baseApiUrl, $defaultPayload);
        $this->response->assertStatus(200)
            ->assertJsonStructure(['t_id', 'expire']);

        $firstTransactionPost = $this->response->decodeResponseJson();

        //Create Second Transaction
        $defaultPayload['items'] = [['f_id' => 10001, 'skus' => [['sku' => 2, 'price' => 2, 'vat' => 2]]]];
        $this->post($this->baseApiUrl, $defaultPayload);
        $this->response->assertStatus(200)
            ->assertJsonStructure(['t_id', 'expire']);
        $secondTransactionPost = $this->response->decodeResponseJson();

        //Get Created Transaction
        $this->get($this->baseApiUrl . '/' . $defaultPayload['fg_id']);
        $this->response->assertStatus(200);

        $transactionData = $this->response->decodeResponseJson();

        $this->assertCount(1, $transactionData);
        $this->assertNotEquals(array_get($firstTransactionPost, 't_id'), array_get($secondTransactionPost, 't_id'));

        //Assert id of the transaction received is the second created
        $this->assertNotEquals(array_get($firstTransactionPost, 't_id'), array_get($transactionData, '0.t_id'));
        $this->assertEquals(array_get($secondTransactionPost, 't_id'), array_get($transactionData, '0.t_id'));
    }

    public function Create()
    {
        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'items' => $items];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, array_get($response_array, 't_id'));
        $this->assertTrue(Carbon::parse($this->getDbNowTime())->lt(array_get($response_array, 'expire')));
        $this->get($base_url . '/' . $post_data['fg_id']);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, count($response_array));
        $this->assertEquals(1, array_get($response_array, '0.t_id'));
        $this->assertEquals('pending', array_get($response_array, '0.status'));

        // Duplicate creation
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200)
            ->assertJson(['t_id' => 1]);

        $items = json_encode([
            ['f_id' => 10001, 'skus' => [['sku' => 2, 'price' => 1, 'vat' => 1]]]
        ]);
        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'items' => $items];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200)
            ->assertJson(['t_id' => 2]);
        $this->get($base_url . '/' . $post_data['fg_id']);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, count($response_array));
        $this->assertEquals(2, array_get($response_array, '0.t_id'));
        $this->assertEquals('pending', array_get($response_array, '0.status'));

        config(['payment_gateway.expiration_minutes' => -20]);
        $items = json_encode([
            ['f_id' => 10001, 'skus' => [['sku' => 3, 'price' => 1, 'vat' => 1]]]
        ]);
        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'items' => $items];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(3, array_get($response_array, 't_id'));
        $this->assertEquals(Carbon::parse($this->getDbNowTime())->subMinutes(config('payment_gateway.expiration_minutes'))->toDateString(), Carbon::parse(array_get($response_array, 'expire'))->toDateString());
        $this->get($base_url . '/' . $post_data['fg_id']);
        $this->response->assertStatus(204);

        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(4, array_get($response_array, 't_id'));
        $this->assertEquals(Carbon::parse($this->getDbNowTime())->subMinutes(config('payment_gateway.expiration_minutes'))->toDateString(), Carbon::parse(array_get($response_array, 'expire'))->toDateString());
        $this->get($base_url . '/' . $post_data['fg_id']);
        $this->response->assertStatus(204);
    }

    public function FetchAll()
    {
        $base_url = 'api/v1/transactions';

        $this->post($base_url);
        $this->response->assertStatus(405);

        $this->get($base_url);
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(0, array_get($response_array, 'total'));
        $this->assertEquals([], array_get($response_array, 'data'));

        $transaction = $this->generateTransaction();

        $this->get($base_url);
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, array_get($response_array, 'total'));
        $this->assertEquals($transaction->t_id, array_get($response_array, 'data.0.t_id'));

        sleep(1);

        $other_transaction = $this->generateTransaction([
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
            't_workflow' => 'vac'
        ]);

        $this->get($base_url);
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(2, array_get($response_array, 'total'));
        $this->assertEquals($other_transaction->t_id, array_get($response_array, 'data.0.t_id'));
        $this->assertEquals($transaction->t_id, array_get($response_array, 'data.1.t_id'));

        $this->get($base_url . '?page=test');
        $this->response->assertStatus(400);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals('params error', array_get($response_array, 'error'));
        $this->assertEquals('The page must be an integer.', array_get($response_array, 'message'));

        $this->get($base_url . '?page=1');
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(2, array_get($response_array, 'total'));
        $this->assertEquals(2, count(array_get($response_array, 'data')));

        $this->get($base_url . '?limit=test');
        $this->response->assertStatus(400);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals('params error', array_get($response_array, 'error'));
        $this->assertEquals('The limit must be an integer.', array_get($response_array, 'message'));

        $this->get($base_url . '?limit=1');
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(2, array_get($response_array, 'total'));
        $this->assertEquals(1, count(array_get($response_array, 'data')));
        $this->assertEquals($other_transaction->t_id, array_get($response_array, 'data.0.t_id'));

        $this->get($base_url . '?page=2&limit=1');
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(2, array_get($response_array, 'total'));
        $this->assertEquals(1, count(array_get($response_array, 'data')));
        $this->assertEquals($transaction->t_id, array_get($response_array, 'data.0.t_id'));

        $this->get($base_url . '?issuer=test');
        $this->response->assertStatus(400);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals('params error', array_get($response_array, 'error'));
        $this->assertEquals('The issuer format is invalid.', array_get($response_array, 'message'));

        $this->get($base_url . '?issuer=dzALG2be');
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, array_get($response_array, 'total'));
        $this->assertEquals($transaction->t_id, array_get($response_array, 'data.0.t_id'));

        $this->get($base_url . '?issuer=dzALG2be,');
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, array_get($response_array, 'total'));
        $this->assertEquals($transaction->t_id, array_get($response_array, 'data.0.t_id'));

        $this->get($base_url . '?issuer=dzALG2be,test');
        $this->response->assertStatus(400);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals('params error', array_get($response_array, 'error'));
        $this->assertEquals('The issuer format is invalid.', array_get($response_array, 'message'));

        $this->get($base_url . '?status=test');
        $this->response->assertStatus(400);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals('params error', array_get($response_array, 'error'));
        $this->assertEquals('The selected status is invalid.', array_get($response_array, 'message'));

        $this->get($base_url . '?status=pending');
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(2, array_get($response_array, 'total'));
        $this->assertEquals($other_transaction->t_id, array_get($response_array, 'data.0.t_id'));

        $this->get($base_url . '?status=pending&issuer=ruMOW2be');
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, array_get($response_array, 'total'));
        $this->assertEquals($other_transaction->t_id, array_get($response_array, 'data.0.t_id'));

        $this->updateTable('transactions', ['t_id' => $transaction->t_id], ['t_status' => 'done']);

        $this->get($base_url . '?page=1&limit=10&issuer=dzALG2be&status=done');
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, array_get($response_array, 'total'));
        $this->assertEquals($transaction->t_id, array_get($response_array, 'data.0.t_id'));

        $this->get($base_url . '?page=2&limit=10&issuer=dzALG2be&status=done');
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, array_get($response_array, 'total'));
        $this->assertEquals([], array_get($response_array, 'data'));

        $this->get($base_url . '?page=1&limit=10&issuer=dzALG2be&status=done&start_date=2021-01-32');
        $this->response->assertStatus(400);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals('params error', array_get($response_array, 'error'));
        $this->assertEquals('The start date is not a valid date.', array_get($response_array, 'message'));

        $this->get($base_url . '?page=1&limit=10&issuer=dzALG2be&status=done&start_date=2021-01-01&end_date=2021-12-32');
        $this->response->assertStatus(400);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals('params error', array_get($response_array, 'error'));
        $this->assertEquals('The end date is not a valid date.', array_get($response_array, 'message'));

        $today = Carbon::today();

        $this->get($base_url . '?page=1&limit=10&issuer=dzALG2be&status=done&start_date=' . $today->toDateString() . '&end_date=' . $today->addDay(1)->toDateString());
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, array_get($response_array, 'total'));
        $this->assertEquals($transaction->t_id, array_get($response_array, 'data.0.t_id'));

        $this->get($base_url . '?page=1&limit=10&issuer=dzALG2be&status=done&start_date=' . $today->addDay(1));
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(0, array_get($response_array, 'total'));
        $this->assertEquals([], array_get($response_array, 'data'));

        $this->get($base_url . '?page=1&limit=10&issuer=dzALG2be&status=done&start_date=' . $today->toDateString() . '&end_date=' . $today->subDays(2));
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(0, array_get($response_array, 'total'));
        $this->assertEquals([], array_get($response_array, 'data'));
    }

    public function defaultPayload(): array
    {
        return [
            [
                [
                    'fg_id' => 10000,
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
                            'f_id' => 10001
                        ],
                    ],
                ]
            ],
        ];
    }
}
