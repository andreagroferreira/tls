<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Support\Carbon;

class KBankControllerTest extends TestCase
{
    protected $amount     = '1.00';
    protected $token      = 'tokn_test_20880fed39e881b9ff7a00cb9bed650a2386b';
    protected $gateway_id = 'chrg_test_2088096fefa79b4514eed9ad5a70059c67a46';
    protected $transactions;

    public function setUp(): void
    {
        parent::setUp();
        // create transaction
        $base_url = '/api/v1/transaction';
        $items = json_encode([['f_id' => 10002, 'skus' => [['sku' => 1, 'price' => $this->amount, 'vat' => 1]]]]);
        $post_data = ['fg_id' => 10001, 'client' => 'be', 'issuer' => 'thBKK2be', 'currency' => 'THB', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'payment_method' => '', 'items' => $items];
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

        $base_url = '/api/v1/k-bank/redirto';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "The t id field is required."
            ]);

        $post_data = ['t_id' => '999999'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "The token field is required."
            ]);

        $responses[] = $this->getFormGroupResponse();
        $this->createSimpleMockResponse($responses);
        $post_data = ['t_id' => $this->transactions->t_id, 'token' => $this->token];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    public function testReturn() {
        putenv('PROJECT=be');
        $base_url = '/api/v1/test/return';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/k-bank/return';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Undefined index: objectId"
            ]);

        $responses[] = $this->getFormGroupResponse();
        $responses[] = $this->getPaymentAction();
        $this->createSimpleMockResponse($responses);
        $transactions = $this->getTransactions(['t_id' => $this->transactions->t_id]);
        $post_data = ['objectId' => $transactions->t_gateway_transaction_id];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    public function testNotify() {
        putenv('PROJECT=be');
        $base_url = '/api/v1/test/notify';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/k-bank/notify';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Undefined index: id"
            ]);

        $transactions = $this->getTransactions(['t_id' => $this->transactions->t_id]);
        $post_data = ['id' => $transactions->t_gateway_transaction_id];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Undefined index: checksum"
            ]);

        $responses[] = $this->getFormGroupResponse();
        $responses[] = $this->getPaymentAction();
        $this->createSimpleMockResponse($responses);
        $post_data = [
            'id'       => $transactions->t_gateway_transaction_id,
            'amount'   => $this->amount,
            'currency' => $this->transactions->t_currency,
            'status'   => 'success',
            'transaction_state' => 'Authorized'
        ];
        $post_data['checksum'] = $this->getHash($post_data);
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    private function getHash($post_data) {
        $secret = env('APP_ENV') === 'production' ? env('KBANK_THBKK2BE_SECRET') : env('KBANK_THBKK2BE_SANDBOX_SECRET');
        $hash_string = $post_data['id'] . number_format($post_data['amount'], 4) . $post_data['currency'] . $post_data['status'] . $post_data['transaction_state'] . $secret;
        return hash('SHA256', $hash_string);
    }
}
