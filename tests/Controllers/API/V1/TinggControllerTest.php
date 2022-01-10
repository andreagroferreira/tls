<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Support\Carbon;

class TinggControllerTest extends TestCase
{
    protected $amount     = '1.00';
    protected $gateway_id = 'dev-test-1634097689';
    protected $transactions;

    public function setUp(): void
    {
        parent::setUp();
        // create transaction
        $base_url = '/api/v1/transaction';
        $items = json_encode([['f_id' => 10002, 'skus' => [['sku' => 1, 'price' => $this->amount, 'vat' => 1]]]]);
        $post_data = ['fg_id' => 10001, 'client' => 'be', 'issuer' => 'snDKR2be', 'currency' => 'XOF', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'payment_method' => '', 'items' => $items];
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

        $base_url = '/api/v1/tingg/redirto';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0011",
                "message" => "Tingg error:transaction_id_not_exists"
            ]);

        $post_data = ['t_id' => '999999'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0011",
                "message" => "Tingg error:transaction_id_not_exists"
            ]);

        $responses[] = $this->getFormGroupResponse();
        $this->createSimpleMockResponse($responses);
        $post_data = ['t_id' => $this->transactions->t_id];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    public function testReturn() {
        putenv('PROJECT=be');
        $base_url = '/api/v1/test/redirto';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/tingg/return';
        $this->post($base_url);
        $this->response->assertStatus(400);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals('fail', array_get($response_array, 'status'));
        $this->assertEquals('P0011', array_get($response_array, 'error'));
        $this->assertEquals('Tingg error:no_data_received', array_get(json_decode(array_get($response_array, 'message'), true), 'message'));

        $post_data = [
            'merchantTransactionID' => str_replace('-', '_', $this->transactions->t_transaction_id)
        ];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals('fail', array_get($response_array, 'status'));
        $this->assertEquals('P0011', array_get($response_array, 'error'));
        $this->assertEquals('Tingg error:no_data_received', array_get(json_decode(array_get($response_array, 'message'), true), 'message'));

        $responses[] = $this->getTinggAuthorization();
        $responses[] = $this->getTinggQueryStatus();
        $responses[] = $this->getFormGroupResponse();
        $responses[] = $this->getPaymentAction();
        $this->createSimpleMockResponse($responses);
        $serviceCode = env('APP_ENV') === 'production' ? env('ENVPAY_TINGG_COMMON_SERVICE_CODE') : env('ENVPAY_TINGG_COMMON_SANDBOX_SERVICE_CODE');
        $post_data = [
            'merchantTransactionID' => str_replace('-', '_', $this->transactions->t_transaction_id),
            'serviceCode'           => $serviceCode
        ];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    public function testNotify() {
        putenv('PROJECT=be');
        $base_url = '/api/v1/test/redirto';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/tingg/notify';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0011",
                "message" => "Tingg error:no_data_received"
            ]);

        $post_data = [
            'merchantTransactionID' => str_replace('-', '_', $this->transactions->t_transaction_id)
        ];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0011",
                "message" => "Tingg error:no_data_received"
            ]);

        $responses[] = $this->getTinggAuthorization();
        $responses[] = $this->getTinggQueryStatus();
        $responses[] = $this->getFormGroupResponse();
        $responses[] = $this->getPaymentAction();
        $this->createSimpleMockResponse($responses);
        $serviceCode = env('APP_ENV') === 'production' ? env('ENVPAY_TINGG_COMMON_SERVICE_CODE') : env('ENVPAY_TINGG_COMMON_SANDBOX_SERVICE_CODE');
        $post_data = [
            'merchantTransactionID' => str_replace('-', '_', $this->transactions->t_transaction_id),
            'serviceCode'           => $serviceCode,
            'requestStatusCode'     => 178,
            'checkoutRequestID'     => '212366',
            'requestStatusDescription' => 'Request fully paid'
        ];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    private function getTinggAuthorization() {
        return [
            'code' => 200,
            'body' => [
                "token_type" => "Bearer",
                "expires_in" => 120,
                "access_token" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjE3MDFlYWNjNzE5MDAwNWViYjUyZjU3YmZjN2IwMjFhYjUxYjEyMjU1NjU2MGEwOTJjYWRiOGJiMzRlNmVlYTVmMjIyYmZlOTMyMTE1OTc1In0.eyJhdWQiOiI0NTUiLCJqdGkiOiIxNzAxZWFjYzcxOTAwMDVlYmI1MmY1N2JmYzdiMDIxYWI1MWIxMjI1NTY1NjBhMDkyY2FkYjhiYjM0ZTZlZWE1ZjIyMmJmZTkzMjExNTk3NSIsImlhdCI6MTU5ODUxMzE4MiwibmJmIjoxNTk4NTEzMTgyLCJleHAiOjE1OTg1MTMzMDIsInN1YiI6IiIsInNjb3BlcyI6W119.s9EUECRnVtcw8kWPSVr93-LVu5EBcv4nPeGAp409hEzvLUufS1hdxS9epvhWKBznG2y-ys9iaacx8aUlkNgocP9Meghrw6yY2VL8g3Y7qLmOAj3vdTAt30DCLZxLhn8bh4ihsb-Kz9FIXt4ed6bW_eWRSwLNO1JJuVi_GZNzWYXITdEa5qOlu2DJe7eKoM0r1JBQPSIjUNG01_qN2dX8HbKNlWKg2FrzuwHPJQe_w-s17OwVAdlmEuOGHVuRX0RYgC7NL1ised6k9Ba3Gz8birLZqapjx1H0gDiQxE1aT5BZL9yxuS94W8pBCiyuDumoRHJ0MmPEdAev-SiGFc9EpRC8V3LUPwYd5HFtMmEgIn0xJ-uhS5SLctQ13u3pVLdz3X_YCGsLi64Ab9zEvAN8u341LHZ41W_qsH0Hp_qB2TpEowYeaJIqdlFSFGkaVozffdEjbHBWn4wEYTdWBNKqfIz8Jv-ok5Q_kiwhgYrVaiROQAP2nukzeIitnbDvOqAxBCnMY7Z3lExKDe7ZdCkZK68IttGSoXOKtpUq8rXbb13os1pjcNEyG7K2de5ehNK0yF32pyQxleQEKPQ9FexfQvCHJ1o-8176UT127Y9KNccDhKI1AZtWcqsBeLz_TS_GY6QVBuQXwBOE2c_V1W4VBO8S30niH7dKDQRWUZDNgTk"
            ]
        ];
    }

    private function getTinggQueryStatus() {
        return [
            'code' => 200,
            'body' => [
                "status" => [
                    "statusCode" => 200,
                    "statusDescription" => "Successfully processed request"
                ],
                "results" => [
                    "checkoutRequestID" => 406287,
                    "merchantTransactionID" => "MTX4148655",
                    "MSISDN" => 25470000000,
                    "accountNumber" => "acc_4148655",
                    "requestDate" => "2020-08-20 09:08:31",
                    "requestStatusCode" => 183,
                    "serviceName" => "Test Servuce",
                    "serviceCode" => "Test Service",
                    "requestCurrencyCode" => "KES",
                    "requestAmount" => 6618,
                    "paymentCurrencyCode" => $this->transactions->t_currency,
                    "amountPaid" => $this->amount,
                    "shortUrl" => "",
                    "redirectTrigger" => "",
                    "payments" => [
                        [
                            "payerTransactionID" => $this->gateway_id,
                            "MSISDN" => 254726806777,
                            "accountNumber" => "acc_4148655",
                            "customerName" => "Customer",
                            "amountPaid" => 6618,
                            "payerClientCode" => "BOA-K",
                            "cpgTransactionID" => "10763559",
                            "paymentDate" => "2020-08-20 09:09:43",
                            "clientName" => "Bank of Africa - Kenya",
                            "clientDisplayName" => "Bank of Africa",
                            "currencyCode" => "KES",
                            "currencyID" => 70,
                            "paymentID" => 2526723,
                            "hubOverallStatus" => 139,
                            "clientCategoryID" => 3,
                            "clientCategoryName" => "Banks",
                            "payerNarration" => null
                        ]
                    ],
                    "failedPayments" => []
                ]
            ]
        ];
    }
}
