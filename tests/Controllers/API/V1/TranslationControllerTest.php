<?php

namespace Tests\Controllers\API\V1;

class TranslationControllerTest extends TestCase
{
    public function testFetch()
    {
        $transaction = $this->generateTransaction();

        $this->get('api/v1/translation/' . 'test');
        $this->response->assertStatus(400);
        $this->get('api/v1/translation/' . 'test?lang=en');
        $this->response->assertStatus(200);

    }
}
