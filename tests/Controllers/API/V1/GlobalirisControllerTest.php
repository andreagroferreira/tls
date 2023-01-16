<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Support\Carbon;

class GlobalirisControllerTest extends TestCase
{
    protected $amount = '1.00';
    protected $transactions;

//    public function setUp(): void
//    {
//        parent::setUp();
//        // create transaction
//        $base_url = '/api/v1/transaction';
//        $items = json_encode([['f_id' => 10002, 'skus' => [['sku' => 1, 'price' => $this->amount, 'vat' => 1]]]]);
//        $post_data = ['fg_id' => 10001, 'client' => 'be', 'issuer' => 'gbLON2be', 'currency' => 'GBP', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'payment_method' => '', 'items' => $items];
//        $this->post($base_url, $post_data);
//        $response_array = $this->response->decodeResponseJson();
//        $this->transactions = $this->getTransactions(['t_id' => array_get($response_array, 't_id')]);
//
//        config(['payment_gateway.be.gbLON2be.globaliris.sandbox.sandbox_merchant_id' => '123456']);
//        config(['payment_gateway.be.gbLON2be.globaliris.sandbox.sandbox_secret' => 'secret']);
//    }
//
//    public function testRedirto()
//    {
//        putenv('PROJECT=be');
//        $base_url = '/api/v1/test/redirto';
//        $this->post($base_url);
//        $this->response->assertStatus(404);
//
//        $base_url = '/api/v1/globaliris/redirto';
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
//        $base_url = '/api/v1/globaliris/return';
//        $this->post($base_url);
//        $this->response->assertStatus(400);
//        $response_array = $this->response->decodeResponseJson();
//        $this->assertEquals('fail', array_get($response_array, 'status'));
//        $this->assertEquals('P0006', array_get($response_array, 'error'));
//        $this->assertEquals('Undefined index: t_client', array_get(json_decode(array_get($response_array, 'message'), true), 'message'));
//
//        $post_data = ['ORDER_ID' => $this->transactions->t_transaction_id];
//        $this->post($base_url, $post_data);
//        $this->response->assertStatus(400);
//        $response_array = $this->response->decodeResponseJson();
//        $this->assertEquals('fail', array_get($response_array, 'status'));
//        $this->assertEquals('P10013', array_get($response_array, 'error'));
//        $this->assertEquals('ONLINE PAYMENT, Globaliris: digital signature check failed', array_get(json_decode(array_get($response_array, 'message'), true), 'message'));
//
//        $responses[] = $this->getFormGroupResponse();
//        $responses[] = $this->getPaymentAction();
//        $this->createSimpleMockResponse($responses);
//        $post_data = [
//            'RESULT'   => '00',
//            'ORDER_ID' => $this->transactions->t_transaction_id,
//            'AMOUNT'   => $this->amount * 100,
//            'TLS_CURRENCY' => $this->transactions->t_currency,
//            'pas_uuid' => 995271901
//        ];
//        $post_data['SHA1HASH'] = $this->getHash($post_data);
//        $this->post($base_url, $post_data);
//        $this->response->assertStatus(200);
//    }
//
//    public function testRedirect() {
//        putenv('PROJECT=be');
//        $base_url = '/api/v1/test/redirect';
//        $this->get($base_url);
//        $this->response->assertStatus(404);
//
//        $base_url = '/api/v1/globaliris/redirect';
//        $this->get($base_url);
//        $this->response->assertStatus(200);
//    }
//
//    private function getHash($post_data) {
//        $merchantid = env('APP_ENV') === 'production' ? config('payment_gateway.be.gbLON2be.globaliris.production.merchant_id') : config('payment_gateway.be.gbLON2be.globaliris.sandbox.sandbox_merchant_id');
//        $secret = env('APP_ENV') === 'production' ? config('payment_gateway.be.gbLON2be.globaliris.production.secret') : 'secret';
//
//        $timestamp = $post_data['TIMESTAMP'] ?? '';
//        $result = $post_data['RESULT'] ?? '';
//        $orderId = $post_data['ORDER_ID'] ?? '';
//        $message = $post_data['MESSAGE'] ?? '';
//        $authcode = $post_data['AUTHCODE'] ?? '';
//        $pasref = $post_data['PASREF'] ?? '';
//        $tmp = "$timestamp.$merchantid.$orderId.$result.$message.$pasref.$authcode";
//        $sha1hash = sha1($tmp);
//        $tmp = "$sha1hash.$secret";
//        return sha1($tmp);
//    }
}
