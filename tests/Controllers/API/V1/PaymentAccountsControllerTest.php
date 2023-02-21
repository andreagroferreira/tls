<?php

namespace Tests\Controllers\API\V1;


use Faker\Core\Number;

class PaymentAccountsControllerTest extends TestCase
{
    protected $pa_id;

    public function testGetPaymentGatewayFieldList()
    {
        putenv('PROJECT=de');
        $base_url = 'api/v1/payment-gateway-field-list';
        $this->get($base_url);
        $this->response->assertStatus(200);

        $responses = [
            'pay_later',
            'alipay',
            'binga',
            'bnp',
            'clictopay',
            'cmi',
            'cybersource',
            'fawry',
            'globaliris',
            'k-bank',
            'payfort',
            'paygate',
            'paypal',
            'paysoft',
            'payu',
            'switch',
            'tingg',
        ];
        $this->assertEquals($responses, array_keys($this->response->json()));
    }

    public function testPaymentAccountsCreate()
    {
        $base_url = 'api/v1/payment-account';
        $this->post($base_url, [
            'pa_xref_psp_id' => 1,
            'pa_name' => 'alipay',
            'pa_type' => 'sandbox',
            'pa_info' => [
                'a' => '123',
                'b' => '234',
            ],
        ]);
        $this->response->assertStatus(200);
    }

    public function testPaymentAccountsFetch()
    {
        $base_url = 'api/v1/payment-account/1';
        $this->get($base_url);
        $this->response->assertStatus(200);
        $this->assertEquals($this->response->json()['pa_id'] ?? '', 1);
    }

    public function testPaymentAccountsPut()
    {
        $base_url = 'api/v1/payment-account/1';
        $this->put($base_url, [
            'pa_name' => 'alipay111',
            'pa_info' => [
                'a' => '123',
                'b' => '234',
            ],
        ]);
        $this->response->assertStatus(200);
        $this->assertEquals($this->response->json()['pa_name'] ?? '', 'alipay111');
    }

    public function testPaymentServiceProviders()
    {
        $base_url = 'api/v1/payment-service-providers';
        $this->get($base_url);
        $this->response->assertStatus(200);
        $responses = [
            'pay_later',
            'alipay',
            'binga',
            'bnp',
            'clictopay',
            'cmi',
            'fawry',
            'globaliris',
            'k-bank',
            'payfort',
            'paygate',
            'paypal',
            'paysoft',
            'payu',
            'switch',
            'tingg',
        ];
        $this->assertTrue(in_array($this->response->json()[0]['psp_code'], $responses));
    }
}
