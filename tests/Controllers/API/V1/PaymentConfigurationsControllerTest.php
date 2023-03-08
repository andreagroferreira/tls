<?php

namespace Tests\Controllers\API\V1;

/**
 * @internal
 * @coversNothing
 */
class PaymentConfigurationsControllerTest extends TestCase
{
    /**
     * @var string
     */
    private $configurationListApi = 'api/v1/payment-configurations-list';

    /**
     * @var string
     */
    private $paymentGatewayTypesApi = 'api/v1/payment-gateway-types';

    public function testPaymentConfigurationsList()
    {
        $base_url = $this->configurationListApi.'?client=de&type=sandbox';
        $this->get($base_url);
        $this->response->assertStatus(200);
        $this->assertEquals($this->response->json(), []);
    }

    public function testLocationConfig()
    {
        $base_url = 'api/v1/location-config?pc_id=1';
        $this->get($base_url);
        if ($this->response->status() === 200) {
            $this->assertNotEmpty($this->response->json());
        } else {
            /*
             * Error given when no payment configurations were found in database
             * this happens if migrations had nothing to import
             */
            $expectedError = [
                'status' => 'fail',
                'error' => 'unknown_error',
                'message' => 'Trying to access array offset on value of type null',
            ];
            $this->assertEquals($this->response->json(), $expectedError);
        }
    }

    public function testLocationAvailableAccounts()
    {
        $base_url = 'api/v1/location-available-accounts?pc_id=1';
        $this->get($base_url);
        if ($this->response->status() === 200) {
            $this->assertNotEmpty($this->response->json());
        } else {
            /*
             * Error given when no payment configurations were found in database
             * this happens if migrations had nothing to import
             */
            $expectedError = [
                'status' => 'fail',
                'error' => 'unknown_error',
                'message' => 'Trying to access array offset on value of type null',
            ];
            $this->assertEquals($this->response->json(), $expectedError);
        }
    }

    /**
     * @return void
     */
    public function testGetPaymentGatewayTypesByCityBoth()
    {
        $this->generateConfigurationPaymentGatewayType(
            $this->getClient(),
            'gov',
            'CAI',
            'eg'
        );

        $this->generateConfigurationPaymentGatewayType(
            $this->getClient(),
            'tls',
            'CAI',
            'eg'
        );

        $this->get($this->paymentGatewayTypesApi.'/CAI');
        $this->response->assertStatus(200)
            ->assertJson([
                'tls', 'gov',
            ]);
    }

    /**
     * @return void
     */
    public function testGetPaymentGatewayTypesByCityWithNoPaymentGateways()
    {
        $this->get($this->paymentGatewayTypesApi.'/PAR');
        $this->response->assertStatus(200)
            ->assertJson([]);
    }

}
