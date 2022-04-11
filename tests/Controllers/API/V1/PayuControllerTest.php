<?php

namespace Tests\Controllers\API\V1;

class PayuControllerTest extends TestCase
{
    protected $amount     = '1.00';
    protected $payment_id = 'aa653d7f-e653-4a64-b989-38d591f8643f';
    protected $gateway_id = '944e8255-4c42-4fef-b5fa-fcbd1c8db47f';
    protected $transactions;

    public function setUp(): void
    {
        parent::setUp();
        // create transaction
        $base_url = '/api/v1/transaction';
        $items = json_encode([['f_id' => 10002, 'skus' => [['sku' => 1, 'price' => $this->amount, 'vat' => 1]]]]);
        $post_data = ['fg_id' => 10001, 'client' => 'be', 'issuer' => 'keNBO2be', 'currency' => 'KES', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'payment_method' => '', 'items' => $items];
        $this->post($base_url, $post_data);
        $response_array = $this->response->decodeResponseJson();
        $this->transactions = $this->getTransactions(['t_id' => array_get($response_array, 't_id')]);
        // update gateway transaction id
        $transaction_service = $this->app->make('App\Services\TransactionService');
        $transaction_service->update(['t_transaction_id' => $this->transactions->t_transaction_id], ['t_gateway_transaction_id' => $this->gateway_id]);
    }

    public function testRedirto()
    {
        putenv('PROJECT=be');
        $base_url = '/api/v1/test/redirto';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/payu/redirto';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Undefined index: t_client"
            ]);

        $post_data = ['t_id' => '999999'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Undefined index: t_client"
            ]);

        $post_data = ['t_id' => $this->transactions->t_id];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    public function testReturn() {
        putenv('PROJECT=be');
        $base_url = '/api/v1/test/return';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/payu/return';
        $this->post($base_url);
        $this->response->assertStatus(400);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals('fail', array_get($response_array, 'status'));
        $this->assertEquals('P0023', array_get($response_array, 'error'));
        $this->assertEquals('payu error:Transaction ERROR: transaction not found', array_get(json_decode(array_get($response_array, 'message'), true), 'message'));

        $responses[] = $this->getFormGroupResponse();
        $responses[] = $this->getPaymentAction();
        $this->createSimpleMockResponse($responses);

        $transactions = $this->getTransactions(['t_id' => $this->transactions->t_id]);
        $this->mockPaymentInitiateService();
        $post_data = [
            'payment_id' => $this->payment_id,
            'charge_id' => $transactions->t_gateway_transaction_id
        ];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    private function mockPaymentInitiateService(): void
    {
        $mocked_result = [
            'result' => [
                'status' => 'Succeed',
            ],
            'amount' => 100
        ];
        $payment_initiate_service_mock = \Mockery::mock('App\Services\PaymentInitiateService');
        $payment_initiate_service_mock->shouldReceive('paymentInitiate')->atLeast(1)->andReturn(json_encode($mocked_result));
        $this->app->instance('App\Services\PaymentInitiateService', $payment_initiate_service_mock);
    }
}
