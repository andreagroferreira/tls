<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Support\Carbon;

class PaygateControllerTest extends TestCase
{
    protected $amount     = '1.00';
    protected $gateway_id = 'BA656064-55D1-15ED-22B7-210D5EB1537B';
    protected $transactions;

//    public function setUp(): void
//    {
//        parent::setUp();
//        // create transaction
//        $base_url = '/api/v1/transaction';
//        $items = json_encode([['f_id' => 10002, 'skus' => [['sku' => 1, 'price' => $this->amount, 'vat' => 1]]]]);
//        $post_data = ['fg_id' => 10001, 'client' => 'be', 'issuer' => 'zaCPT2be', 'currency' => 'ZAR', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'payment_method' => '', 'items' => $items];
//        $this->post($base_url, $post_data);
//        $response_array = $this->response->decodeResponseJson();
//        $this->transactions = $this->getTransactions(['t_id' => array_get($response_array, 't_id')]);
//        // update gateway transaction id
//        $transaction_service = $this->app->make('App\Services\TransactionService');
//        $transaction_service->update(['t_transaction_id' => $this->transactions->t_transaction_id], ['t_gateway_transaction_id' => $this->gateway_id]);
//    }
//
//    public function testRedirto()
//    {
//        putenv('PROJECT=be');
//        $base_url = '/api/v1/test/redirto';
//        $this->post($base_url);
//        $this->response->assertStatus(404);
//
//        $base_url = '/api/v1/paygate/redirto';
//        $this->post($base_url);
//        $this->response->assertStatus(400)
//            ->assertJson([
//                "status" => "fail",
//                "error" => "P0006",
//                "message" => "Undefined index: t_client"
//            ]);
//
//        $post_data = ['t_id' => '999999'];
//        $this->post($base_url, $post_data);
//        $this->response->assertStatus(400)
//            ->assertJson([
//                "status" => "fail",
//                "error" => "P0006",
//                "message" => "Undefined index: t_client"
//            ]);
//
//        $responses[] = $this->getFormGroupResponse();
//        $this->createSimpleMockResponse($responses);
//        $post_data = ['t_id' => $this->transactions->t_id];
//        $this->post($base_url, $post_data);
//        $this->response->assertStatus(200);
//    }
//
//    public function testReturn() {
//        putenv('PROJECT=be');
//        $base_url = '/api/v1/test/return';
//        $this->post($base_url);
//        $this->response->assertStatus(404);
//
//        $base_url = '/api/v1/paygate/return';
//        $this->post($base_url);
//        $this->response->assertStatus(400);
//        $response_array = $this->response->decodeResponseJson();
//        $this->assertEquals('fail', array_get($response_array, 'status'));
//        $this->assertEquals('P0006', array_get($response_array, 'error'));
//        $this->assertEquals('Undefined index: PAY_REQUEST_ID', array_get(json_decode(array_get($response_array, 'message'), true), 'message'));
//
//        $transactions = $this->getTransactions(['t_id' => $this->transactions->t_id]);
//        $post_data = ['PAY_REQUEST_ID' => $transactions->t_gateway_transaction_id];
//        $this->post($base_url, $post_data);
//        $this->response->assertStatus(400);
//        $response_array = $this->response->decodeResponseJson();
//        $this->assertEquals('fail', array_get($response_array, 'status'));
//        $this->assertEquals('P0006', array_get($response_array, 'error'));
//        $this->assertEquals('Undefined index: TRANSACTION_STATUS', array_get(json_decode(array_get($response_array, 'message'), true), 'message'));
//
//        $responses[] = $this->getFormGroupResponse();
//        $responses[] = $this->getPaymentAction();
//        $this->createSimpleMockResponse($responses);
//        $post_data = [
//            'PAY_REQUEST_ID'     => $transactions->t_gateway_transaction_id,
//            'TRANSACTION_STATUS' => 1
//        ];
//        $this->post($base_url, $post_data);
//        $this->response->assertStatus(200);
//    }
//
//    public function testNotify() {
//        putenv('PROJECT=be');
//        $base_url = '/api/v1/test/notify';
//        $this->post($base_url);
//        $this->response->assertStatus(404);
//
//        $base_url = '/api/v1/paygate/notify';
//        $this->post($base_url);
//        $this->response->assertStatus(400)
//            ->assertJson([
//                "status" => "fail",
//                "error" => "P0006",
//                "message" => "Undefined index: t_client"
//            ]);
//
//        $transactions = $this->getTransactions(['t_id' => $this->transactions->t_id]);
//        $post_data = ['PAY_REQUEST_ID' => $transactions->t_gateway_transaction_id];
//        $this->post($base_url, $post_data);
//        $this->response->assertStatus(400)
//            ->assertJson([
//                "status" => "fail",
//                "error" => "P0006",
//                "message" => "Undefined index: AMOUNT"
//            ]);
//
//        $responses[] = $this->getFormGroupResponse();
//        $responses[] = $this->getPaymentAction();
//        $this->createSimpleMockResponse($responses);
//        $post_data = [
//            'AMOUNT'   => $this->amount,
//            'CURRENCY' => $this->transactions->t_currency,
//            'PAY_REQUEST_ID'     => $transactions->t_gateway_transaction_id,
//            'TRANSACTION_STATUS' => 1
//        ];
//        $post_data['CHECKSUM'] = $this->getHash($post_data);
//        $this->post($base_url, $post_data);
//        $this->response->assertStatus(200);
//    }
//
//    private function getHash($post_data) {
//        $encryptionKey = env('APP_ENV') === 'production' ? env('PAYGATE_ZAALL2BE_ENCRYPTION_KEY') : env('PAYGATE_ZAALL2BE_ENCRYPTION_SANDBOX_KEY');
//        $tmp = $post_data['PAY_REQUEST_ID'] . $post_data['TRANSACTION_STATUS'] . $encryptionKey;
//        return md5($tmp);
//    }
}
