<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Support\Carbon;

class ClictopayControllerTest extends TestCase
{
    protected $amount     = '1.00';
    protected $gateway_id = 'visa-be-dev-tnTUN2be-0000000001';
    protected $transactions;

//    public function setUp(): void
//    {
//        parent::setUp();
//        // create transaction
//        $base_url = '/api/v1/transaction';
//        $items = json_encode([['f_id' => 10002, 'skus' => [['sku' => 1, 'price' => $this->amount, 'vat' => 1]]]]);
//        $post_data = ['fg_id' => 10001, 'client' => 'be', 'issuer' => 'tnTUN2be', 'currency' => 'TND', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'payment_method' => '', 'items' => $items];
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
//        $base_url = '/api/v1/clictopay/redirto';
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
//        $responses[] = $this->getrRegisterDo();
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
//        $base_url = '/api/v1/clictopay/return';
//        $this->post($base_url);
//        $this->response->assertStatus(400);
//        $response_array = $this->response->decodeResponseJson();
//        $this->assertEquals('fail', array_get($response_array, 'status'));
//        $this->assertEquals('P0006', array_get($response_array, 'error'));
//        $this->assertEquals('Undefined index: lang', array_get(json_decode(array_get($response_array, 'message'), true), 'message'));
//
//        $responses[] = $this->getOrderStatusExtended();
//        $responses[] = $this->getFormGroupResponse();
//        $responses[] = $this->getPaymentAction();
//        $this->createSimpleMockResponse($responses);
//        $transactions = $this->getTransactions(['t_id' => $this->transactions->t_id]);
//        $post_data = [
//            'orderId' => $transactions->t_gateway_transaction_id,
//            'lang' => 'en'
//        ];
//        $this->post($base_url, $post_data);
//        $this->response->assertStatus(200);
//    }
//
//    private function getrRegisterDo() {
//        return [
//            'code' => 200,
//            'body' => [
//                "orderId" => $this->gateway_id,
//                "formUrl" => "https://test.clictopay.com/payment/merchants/CLICTOPAY/payment_en.html?mdOrder=" . $this->gateway_id
//            ]
//        ];
//    }
//
//    private function getOrderStatusExtended() {
//        return [
//            'code' => 200,
//            'body' => [
//                "errorCode" => "0",
//                "errorMessage" => "Success",
//                "orderNumber" => $this->gateway_id,
//                "orderStatus" => 2,
//                "actionCode" => 0,
//                "actionCodeDescription" => "Request processed successfully",
//                "originalActionCode" => "0",
//                "amount" => $this->amount * 1000,
//                "currency" => "788",
//                "date" => 1636016433286,
//                "depositedDate" => 1636016475553,
//                "orderDescription" => "",
//                "ip" => "172.17.3.50",
//                "merchantOrderParams" => [
//                    [
//                        "name" => "browser_language_param",
//                        "value" => "zh"
//                    ],
//                    [
//                        "name" => "browser_os_param",
//                        "value" => "WINDOWS"
//                    ],
//                    [
//                        "name" => "browser_version_param",
//                        "value" => "95.0.4638.69"
//                    ],
//                    [
//                        "name" => "user_agent",
//                        "value" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.69 Safari/537.36"
//                    ],
//                    [
//                        "name" => "browser_name_param",
//                        "value" => "CHROME"
//                    ]
//                ],
//                "transactionAttributes" => [],
//                "attributes" => [
//                    [
//                        "name" => "mdOrder",
//                        "value" => "f29a4be0-6698-7540-97e2-6a5b00bc52f9"
//                    ]
//                ],
//                "cardAuthInfo" => [
//                    "maskedPan" => "455769**1113",
//                    "expiration" => "202412",
//                    "cardholderName" => "a b",
//                    "approvalCode" => "187876",
//                    "paymentSystem" => "VISA",
//                    "pan" => "455769**1113"
//                ],
//                "authDateTime" => 1636016475410,
//                "terminalId" => "01234567",
//                "authRefNum" => "000062187876",
//                "paymentAmountInfo" => [
//                    "paymentState" => "DEPOSITED",
//                    "approvedAmount" => 100000,
//                    "depositedAmount" => 100000,
//                    "refundedAmount" => 0,
//                    "feeAmount" => 0
//                ],
//                "bankInfo" => [
//                        "bankName" => "Banque de Tunisie",
//                    "bankCountryCode" => "TN",
//                    "bankCountryName" => "Tunisia"
//                ],
//                "payerData" => [
//                        "email" => "a@0.com"
//                ],
//                "chargeback" => false,
//                "paymentWay" => "CARD"
//            ]
//        ];
//    }
}
