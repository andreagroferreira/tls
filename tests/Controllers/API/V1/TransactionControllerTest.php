<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Support\Carbon;

class TransactionControllerTest extends TestCase
{
    public function testFetch()
    {
        $base_url = 'api/v1/transaction';

        $this->get($base_url);
        $this->response->assertStatus(405);

        $this->get($base_url . '/test');
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The fg id must be an integer.',
            ]);

        $this->get($base_url . '/1');
        $this->response->assertStatus(404);

        $transaction = $this->generateTransaction();
        $this->generateTransactionItems($transaction->t_transaction_id);
        $transaction_items = $this->generateTransactionItems($transaction->t_transaction_id);

        $this->get($base_url . '/' . $transaction->t_xref_fg_id);
        $response_array = $this->response->decodeResponseJson();
        $this->response->assertStatus(200);
        $this->assertEquals(1, count($response_array));
        $this->assertEquals(1, array_get($response_array, '0.t_id'));
        $this->assertEquals($transaction->t_transaction_id, array_get($response_array, '0.transaction_id'));
        $this->assertEquals('pending', array_get($response_array, '0.status'));
        $this->assertEquals(1, count(array_get($response_array, '0.items')));
        $this->assertEquals($transaction_items->ti_xref_f_id, array_get($response_array, '0.items.0.f_id'));
        $this->assertEquals(2, count(array_get($response_array, '0.items.0.skus')));
    }

    public function testCreate()
    {
        $base_url = 'api/v1/transaction';

        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The fg id field is required.'
            ]);

        $post_data = ['fg_id' => 'test'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The fg id must be an integer.'
            ]);

        $post_data = ['fg_id' => 10000];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The client field is required.'
            ]);

        $post_data = ['fg_id' => 10000, 'client' => 'be'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The issuer field is required.'
            ]);

        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The currency field is required.'
            ]);

        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The redirect url field is required.'
            ]);

        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The onerror url field is required.'
            ]);

        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The reminder url field is required.'
            ]);

        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The reminder url field is required.'
            ]);

        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The callback url field is required.'
            ]);

        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The workflow field is required.'
            ]);

        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items field is required.'
            ]);

        $items = json_encode([
            ['test' => 'test']
        ]);
        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'items' => $items];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.skus field is required.'
            ]);

        $items = json_encode([
            ['test' => 'test', 'skus' => [['sku' => 1]]]
        ]);
        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'items' => $items];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.skus.price, vat field is required.'
            ]);

        $items = json_encode([
            ['test' => 'test', 'skus' => [['sku' => 1, 'price' => 1]]]
        ]);
        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'items' => $items];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.skus.vat field is required.'
            ]);

        $items = json_encode([
            ['f_id' => '', 'skus' => [['sku' => 1, 'price' => 1, 'vat' => 1]]]
        ]);
        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'items' => $items];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                'error' => 'params error',
                'message' => 'The items.f_id must be an integer.'
            ]);

        $items = json_encode([
            ['f_id' => 10001, 'skus' => [['sku' => 1, 'price' => 1, 'vat' => 1]]]
        ]);
        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'items' => $items];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, array_get($response_array, 't_id'));
        $this->assertTrue(Carbon::parse($this->getDbNowTime())->lt(array_get($response_array, 'expire')));
        $this->get($base_url . '/' . $post_data['fg_id']);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, count($response_array));
        $this->assertEquals(1, array_get($response_array, '0.t_id'));
        $this->assertEquals('pending', array_get($response_array, '0.status'));

        // Duplicate creation
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200)
            ->assertJson(['t_id' => 1]);

        $items = json_encode([
            ['f_id' => 10001, 'skus' => [['sku' => 2, 'price' => 1, 'vat' => 1]]]
        ]);
        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'items' => $items];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200)
            ->assertJson(['t_id' => 2]);
        $this->get($base_url . '/' . $post_data['fg_id']);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(1, count($response_array));
        $this->assertEquals(2, array_get($response_array, '0.t_id'));
        $this->assertEquals('pending', array_get($response_array, '0.status'));

        config(['payment_gateway.expiration_minutes' => -20]);
        $items = json_encode([
            ['f_id' => 10001, 'skus' => [['sku' => 3, 'price' => 1, 'vat' => 1]]]
        ]);
        $post_data = ['fg_id' => 10000, 'client' => 'be', 'issuer' => 'dzALG2fr', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'items' => $items];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(3, array_get($response_array, 't_id'));
        $this->assertEquals(Carbon::parse($this->getDbNowTime())->subMinutes(config('payment_gateway.expiration_minutes'))->toDateString(), Carbon::parse(array_get($response_array, 'expire'))->toDateString());
        $this->get($base_url . '/' . $post_data['fg_id']);
        $this->response->assertStatus(404);

        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals(4, array_get($response_array, 't_id'));
        $this->assertEquals(Carbon::parse($this->getDbNowTime())->subMinutes(config('payment_gateway.expiration_minutes'))->toDateString(), Carbon::parse(array_get($response_array, 'expire'))->toDateString());
        $this->get($base_url . '/' . $post_data['fg_id']);
        $this->response->assertStatus(404);
    }
}
