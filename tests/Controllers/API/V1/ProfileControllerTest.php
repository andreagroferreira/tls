<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Support\Carbon;

class ProfileControllerTest extends TestCase
{

    public function testFetchApplications()
    {
        $base_url = 'api/v1/application-with-profile';

        $this->post($base_url);
        $this->response->assertStatus(404);

        $this->get($base_url);
        $this->response->assertStatus(404);

        $this->get($base_url . '/UK-SMS');
        $this->response->assertStatus(200);
    }

    public function testFetchProfile()
    {
        $base_url = 'api/v1/application-profile';

        $this->post($base_url);
        $this->response->assertStatus(404);

        $this->get($base_url);
        $this->response->assertStatus(404);

        $this->get($base_url . '/10001');
        $this->response->assertStatus(200);
    }

    public function testFetchMultiProfiles()
    {
        $base_url = 'api/v1/application-profiles';

        $this->get($base_url);
        $this->response->assertStatus(405);

        $this->post($base_url);
        $this->response->assertStatus(400);

        $data = [
            'f_ids' => [
                10001,
                10002
            ]
        ];
        $this->post($base_url, $data);
        $this->response->assertStatus(200);
    }
}
