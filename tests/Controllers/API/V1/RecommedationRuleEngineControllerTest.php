<?php

namespace Tests\Controllers\API\V1;

class RecommedationRuleEngineControllerTest extends TestCase
{
    private $condition = [
        'issuer' => 'esMAD2uk',
        'visa_type' => 'short_stay',
        'travel_purpose' => 'business',
        'age' => '100',
        'nationality' => 'cn',
        'account_type' => 'INDI',
        'step' => 'Welcome',
        'visa_sub_type' => 'ext',
        'top' => '6'
    ];

    public function testFetch() {
        putenv('PROJECT=uk');
        $base_url = 'api/v1/rcd_rule';

        $this->get($base_url);
        $this->response->assertStatus(405);

        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                'status' => 'fail',
                'error' => 'params error',
                'message' => 'The issuer must be a string.',
            ]);

        $this->post($base_url, $this->condition);
        $this->response->assertStatus(200)
            ->assertJsonCount(6);

        $this->post($base_url, $this->modifyCondition('top', 4));
        $this->response->assertStatus(200)
            ->assertJsonCount(4);

        $this->post($base_url, $this->modifyCondition('issuer', 'frPAR2uk'));
        $this->response->assertStatus(200)
            ->assertJsonCount(0);

        $this->post($base_url, $this->modifyCondition('visa_type', 'long_stay'));
        $this->response->assertStatus(200)
            ->assertJsonCount(5)
            ->assertJsonMissing(['Keep My Pax']);

        $this->post($base_url, $this->modifyCondition('age', '23'));
        $this->response->assertStatus(200)
            ->assertJsonCount(5)
            ->assertJsonMissing(['Premium']);

        $this->post($base_url, $this->modifyCondition('step', 'Bio'));
        $this->response->assertStatus(200)
            ->assertJsonCount(5)
            ->assertJsonMissing(['Premium']);
    }

    private function modifyCondition($key, $value) {
        $condition = $this->condition;
        $condition[$key] = $value;
        return $condition;
    }
}
