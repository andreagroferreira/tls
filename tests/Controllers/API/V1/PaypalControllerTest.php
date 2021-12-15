<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Support\Carbon;

class PaypalControllerTest extends TestCase
{
    protected $amount     = '1.00';
    protected $gateway_id = 'BA656064-55D1-15ED-22B7-210D5EB1537B';
    protected $transactions;

    public function setUp(): void
    {
        parent::setUp();
        // create transaction
        $base_url = '/api/v1/transaction';
        $items = json_encode([['f_id' => 10002, 'skus' => [['sku' => 1, 'price' => $this->amount, 'vat' => 1]]]]);
        $post_data = ['fg_id' => 10001, 'client' => 'be', 'issuer' => 'ruMOW2be', 'currency' => 'RUB', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'payment_method' => '', 'items' => $items];
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

        $base_url = '/api/v1/paypal/redirto';
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

        $responses[] = $this->getFormGroupResponse();
        $this->createSimpleMockResponse($responses);
        $post_data = ['t_id' => $this->transactions->t_id];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    public function testReturn() {
        putenv('PROJECT=be');
        $base_url = '/api/v1/test/return';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/paypal/return';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Undefined index: t_redirect_url"
            ]);

        $post_data = ['t_id' => '999999'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Undefined index: t_redirect_url"
            ]);

        $post_data = ['t_id' => $this->transactions->t_id];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    public function testNotify() {
        putenv('PROJECT=be');
        $base_url = '/api/v1/test/notify';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/paypal/notify';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Undefined index: transid"
            ]);

        $transactions = $this->getTransactions(['t_id' => $this->transactions->t_id]);
        $responses[] = $this->getFormGroupResponse();
        $responses[] = $this->getPaymentAction();
        $this->createSimpleMockResponse($responses);
        $post_data = [
            'transid' => $transactions->t_transaction_id,
            'fg_id'   => $transactions->t_xref_fg_id,
            'payment_status' => 'Completed',
            'mc_gross'       => $this->amount,
            'mc_currency'    => $transactions->t_currency,
            'txn_id'         => $transactions->t_gateway_transaction_id
        ];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'status' => 'fail',
                'error'  => 'P0021',
                'message' => 'paypal error:["Transaction could not be verified."]'
            ]);
    }
}
