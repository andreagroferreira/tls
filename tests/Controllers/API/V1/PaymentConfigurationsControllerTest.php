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

    public function setUp(): void
    {
        parent::setUp();
        putenv('APP_ENV=testing');
    }

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
