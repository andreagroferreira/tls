<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Support\Carbon;

class FawryControllerTest extends TestCase
{
    protected $amount = '1.00';
    protected $transactions;

    public function setUp(): void
    {
        parent::setUp();
        // create transaction
        $base_url = '/api/v1/transaction';
        $items = json_encode([['f_id' => 10001, 'skus' => [['sku' => 1, 'price' => $this->amount, 'vat' => 1]]]]);
        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'egCAI2be', 'currency' => 'EGP', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'payment_method' => '', 'items' => $items];
        $this->post($base_url, $post_data);
        $response_array = $this->response->decodeResponseJson();
        $this->transactions = $this->getTransactions(['t_id' => array_get($response_array, 't_id')]);
    }

    public function testRedirto()
    {
        putenv('PROJECT=be');
        $base_url = '/api/v1/test/redirto';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/fawry/redirto';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Undefined index: t_id"
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

        $base_url = '/api/v1/fawry/return';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0009",
                "message" => "no_data_received"
            ]);

        $post_data = ['merchantRefNumber' => $this->transactions->t_transaction_id];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "unknown_error: transaction_has_not_been_paid"
            ]);

        $responses[] = $this->getFormGroupResponse();
        $responses[] = $this->getPaymentAction();
        $this->createSimpleMockResponse($responses);
        $post_data = [
            'statusCode'        => 200,
            'orderStatus'       => 'PAID',
            'merchantRefNumber' => $this->transactions->t_transaction_id
        ];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    public function testNotify() {
        putenv('PROJECT=be');
        $base_url = '/api/v1/test/notify';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/fawry/notify';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0009",
                "message" => "no_data_received"
            ]);

        $post_data = ['merchantRefNumber' => $this->transactions->t_transaction_id];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Undefined index: paymentAmount"
            ]);

        $responses[] = $this->getFormGroupResponse();
        $responses[] = $this->getPaymentAction();
        $this->createSimpleMockResponse($responses);
        $post_data = [
            'merchantRefNumber' => $this->transactions->t_transaction_id,
            'fawryRefNumber'    => '995271901',
            'paymentAmount'     => $this->amount,
            'orderAmount'       => $this->amount,
            'orderStatus'       => 'PAID',
            'paymentMethod'     => 'PAYATFAWRY',
            'paymentRefrenceNumber' => ''
        ];
        $post_data['messageSignature'] = $this->getHash($post_data);
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    private function getHash($post_data) {
        $secret = env('APP_ENV') === 'production' ? $this->getEnvpayValue('ENVPAY_FAW_egAll2be_SECURITY_KEY') : $this->getEnvpayValue('ENVPAY_FAW_COMMON_SANDBOX_SECRET_KEY');
        $sign_string = $post_data['fawryRefNumber'] . $post_data['merchantRefNumber'] . $post_data['paymentAmount'] . $post_data['orderAmount'] . $post_data['orderStatus'] . $post_data['paymentMethod'] . $post_data['paymentRefrenceNumber'] . $secret;
        return hash('SHA256', $sign_string);
    }

    private function getEnvpayValue($env_key) {
        $suffix = 'ENVPAY_';
        if (strtoupper(substr($env_key, 0, 7)) !== $suffix) {
            return $env_key;
        }
        return getenv(substr($env_key, 7));
    }
}
