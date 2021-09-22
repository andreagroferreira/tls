<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecommendationResultControllerTest extends TestCase
{
    private $rcd_example = [
        'rr_xref_f_id' => 10000,
        'rr_agent' => 'test.test',
        'rr_sku' => 'COURIER',
        'rr_result' => 'accept'
    ];

    public function testCreate()
    {
        $base_url = 'api/v1/rcd_result';

        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The rr xref f id field is required.'
            ]);

        $post_data = ['f_id' => 'test'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The rr xref f id must be an integer.'
            ]);

        $post_data = ['f_id' => 10000];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The rr agent field is required.'
            ]);

        $post_data = ['f_id' => 10000, 'agent' => 'test.test'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The rr sku field is required.'
            ]);

        $post_data = ['f_id' => 10000, 'agent' => 'test.test', 'sku' => 'COURIER'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The rr result field is required.'
            ]);

        $post_data = ['f_id' => 10000, 'agent' => 'test.test', 'sku' => 'COURIER', 'result' => 'accept'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, array_get($response_array, 'rr_id'));
    }

    public function testFetchAll()
    {
        $base_url = 'api/v1/rcd_result';

        $this->get($base_url);
        $this->response->assertStatus(405);

        $transaction = $this->generateRcd($this->rcd_example);
        $this->get($base_url . '/' . $this->rcd_example['rr_xref_f_id']);
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, count($response_array));
        $this->assertEquals(1, array_get($response_array, '0.rr_id'));
    }

    public function testDelete()
    {
        $base_url = 'api/v1/rcd_result';
        $transaction = $this->generateRcd($this->rcd_example);
        $this->get($base_url . '/' . $this->rcd_example['rr_xref_f_id']);
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, count($response_array));
        $rcd_id = array_get($response_array, '0.rr_id');
        $this->delete($base_url . '/' . $rcd_id, [
            'rr_deleted_by' => 1
        ]);
        $this->response->assertStatus(200);
        $this->get($base_url . '/' . $this->rcd_example['rr_xref_f_id']);
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(0, count($response_array));
        $query = $this->queryDelete($rcd_id);
        $this->assertEquals(false, blank($query));

        $this->delete($base_url . '/' . $rcd_id, [
            'rr_deleted_by' => 1,
            'is_soft_delete' => 'no'
        ]);
        $this->response->assertStatus(200);
        $this->get($base_url . '/' . $this->rcd_example['rr_xref_f_id']);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(0, count($response_array));
        $query = $this->queryDelete($rcd_id);
        $this->assertEquals(true, blank($query));
    }

    private function queryDelete($rcd_id)
    {
        return DB::connection('unit_test_payment_pgsql')->table('recommendation_result')->where('rr_id', $rcd_id)->get();
    }
}
