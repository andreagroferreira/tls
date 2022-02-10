<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Support\Carbon;

class PaysoftControllerTest extends TestCase
{
    protected $amount = '1.00';
    protected $transactions;
    protected $merchantId = '1234';
    protected $signatureAlgorithm = 'sha256';
    protected $signatureSecretKey = 'test';

    public function setUp(): void
    {
        parent::setUp();
        // create transaction
        $base_url = '/api/v1/transaction';
        $items = json_encode([['f_id' => 10002, 'skus' => [['sku' => 1, 'price' => $this->amount, 'vat' => 1]]]]);
        $post_data = ['fg_id' => 10001, 'client' => 'pl', 'issuer' => 'uaKBP2pl', 'currency' => 'UAH', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'payment_method' => '', 'items' => $items];
        $this->post($base_url, $post_data);
        $response_array = $this->response->decodeResponseJson();
        $this->transactions = $this->getTransactions(['t_id' => array_get($response_array, 't_id')]);

        config(['payment_gateway.pl.uaKBP2pl.paysoft' => [
            'label' => 'Paysoft pay',
            'active' => true,
            'common' => [
                'env' => 'live',
                'return_url' => '/paysoft/return',
            ],
            'sandbox' => [
                'host' => '',
                'merchant_id' => $this->merchantId,
                'signature_algorithm' => $this->signatureAlgorithm,
                'signature_secret_key' => $this->signatureSecretKey,
            ]]]);
    }

    public function testRedirto()
    {
        putenv('PROJECT=pl');
        $base_url = '/api/v1/test/redirto';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/paysoft/redirto';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0001",
                "message" => "Transaction ERROR: transaction not found"
            ]);

        $post_data = ['t_id' => '999999'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0001",
                "message" => "Transaction ERROR: transaction not found"
            ]);

        $responses[] = $this->getFormGroupResponse();
        $this->createSimpleMockResponse($responses);
        $post_data = ['t_id' => $this->transactions->t_id];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    public function testNotify()
    {
        putenv('PROJECT=pl');
        $base_url = '/api/v1/test/notify';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/paysoft/notify';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Illegal parameter"
            ]);

        $post_data = ['LMI_PAYMENT_NO' => $this->transactions->t_transaction_id];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400);

        $post_data = ['LMI_PAYMENT_NO' => $this->transactions->t_transaction_id, 'LMI_MERCHANT_ID' => $this->merchantId, 'LMI_PAYMENT_AMOUNT' => $this->amount];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
        $this->assertEquals('"YES"', $this->response->getContent());

        $responses[] = $this->getFormGroupResponse();
        $responses[] = $this->getPaymentAction();
        $this->createSimpleMockResponse($responses);
        $post_data = [
            'LMI_MERCHANT_ID' => $this->merchantId,
            'LMI_PAYMENT_NO' => $this->transactions->t_transaction_id,
            'LMI_SYS_PAYMENT_ID' => rand(100000000, 999999999),
            'LMI_SYS_PAYMENT_DATE' => Carbon::now()->toDateTimeString(),
            'LMI_PAYMENT_AMOUNT' => $this->amount,
            'LMI_PAID_AMOUNT' => $this->amount,
            'LMI_PAYMENT_SYSTEM' => 21,
            'LMI_MODE' => 1,
        ];
        $post_data['LMI_HASH'] = strtoupper(hash($this->signatureAlgorithm, implode('', $post_data) . $this->signatureSecretKey));

        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    public function testReturn()
    {
        putenv('PROJECT=pl');
        $base_url = '/api/v1/test/return';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/paysoft/return';
        $this->post($base_url);
        $this->response->assertStatus(400);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals('fail', array_get($response_array, 'status'));
        $this->assertEquals('P0006', array_get($response_array, 'error'));
        $this->assertEquals('Transaction ERROR: transaction not found.', array_get(json_decode(array_get($response_array, 'message'), true), 'message'));

        $post_data = ['LMI_PAYMENT_NO' => $this->transactions->t_transaction_id,];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals('fail', array_get($response_array, 'status'));
        $this->assertEquals('P0006', array_get($response_array, 'error'));
        $this->assertEquals('Transaction ERROR: Pay Failure.', array_get(json_decode(array_get($response_array, 'message'), true), 'message'));

        $t_gateway_transaction_id = rand(100000000, 999999999);
        $update_fields = [
            't_gateway' => 'paysoft',
            't_gateway_transaction_id' => $t_gateway_transaction_id,
            't_status' => 'done'
        ];
        $this->updateTable('transactions', ['t_transaction_id' => $this->transactions->t_transaction_id], $update_fields);

        $post_data = ['LMI_PAYMENT_NO' => $this->transactions->t_transaction_id, 'LMI_SYS_PAYMENT_ID' => $t_gateway_transaction_id];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals('ok', $response_array['is_success']);
        $this->assertEquals($this->transactions->t_transaction_id, $response_array['orderid']);
    }
}
