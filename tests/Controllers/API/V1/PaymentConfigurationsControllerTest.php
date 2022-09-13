<?php

namespace Tests\Controllers\API\V1;


class PaymentConfigurationsControllerTest extends TestCase
{

    public function testPaymentConfigurationsList()
    {
        $base_url = 'api/v1/payment-configurations-list?client=de&type=sandbox';
        $this->get($base_url);
        $this->response->assertStatus(200);
        $this->assertEquals($this->response->json(), []);
    }

    public function testLocationConfig()
    {
        $base_url = 'api/v1/location-config?pc_id=1';
        $this->get($base_url);
        $this->response->assertStatus(200);
        $this->assertNotEmpty($this->response->json());
    }

    public function testLocationAvailableAccounts()
    {
        $base_url = 'api/v1/location-available-accounts?pc_id=1';
        $this->get($base_url);
        $this->response->assertStatus(200);
        $this->assertNotEmpty($this->response->json());
    }

}
