<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Support\Carbon;

class CmiControllerTest extends TestCase
{
    protected $amount = '1.00';
    protected $transactions;

    public function setUp(): void
    {
        parent::setUp();
        // create transaction
        $base_url = '/api/v1/transaction';
        $items = json_encode([['f_id' => 10002, 'skus' => [['sku' => 1, 'price' => $this->amount, 'vat' => 1]]]]);
        $post_data = ['fg_id' => 10001, 'client' => 'be', 'issuer' => 'maCAS2be', 'currency' => 'MAD', 'redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr', 'onerror_url' => 'onError_tlsweb_url?lang=fr-fr', 'reminder_url' => 'callback_to_send_reminder?lang=fr-fr', 'callback_url' => 'receipt_url/{fg_id}?lang=fr-fr', 'workflow' => 'vac', 'payment_method' => '', 'items' => $items];
        $this->post($base_url, $post_data);
        $response_array = $this->response->decodeResponseJson();
        $this->transactions = $this->getTransactions(['t_id' => array_get($response_array, 't_id')]);
    }

    public function testRedirto()
    {
        putenv('PROJECT=be');
        $base_url = '/api/v1/test/redirto';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/cmi/redirto';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Transaction ERROR: transaction not found"
            ]);

        $post_data = ['t_id' => '999999'];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Transaction ERROR: transaction not found"
            ]);

        $responses[] = $this->getFormGroupResponse();
        $this->createSimpleMockResponse($responses);
        $post_data = ['t_id' => $this->transactions->t_id];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    public function testReturn() {
        putenv('PROJECT=be');
        $base_url = '/api/v1/test/return';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/cmi/return';
        $this->post($base_url);
        $this->response->assertStatus(400);
        $response_array = $this->response->decodeResponseJson();
        $this->assertEquals('fail', array_get($response_array, 'status'));
        $this->assertEquals('P0006', array_get($response_array, 'error'));
        $this->assertEquals('Transaction ERROR: transaction not found', array_get(json_decode(array_get($response_array, 'message'), true), 'message'));

        $post_data = ['oid' => $this->transactions->t_transaction_id];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Undefined index: HASH"
            ]);

        $responses[] = $this->getFormGroupResponse();
        $responses[] = $this->getPaymentAction();
        $this->createSimpleMockResponse($responses);
        $post_data = [
            'amount'   => $this->amount,
            'currency' => $this->transactions->t_currency,
            'oid'      => $this->transactions->t_transaction_id,
            'TransId'  => '995271901'
        ];
        $post_data['HASH'] = $this->getHash($post_data);
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    public function testNotify() {
        putenv('PROJECT=be');
        $base_url = '/api/v1/test/notify';
        $this->post($base_url);
        $this->response->assertStatus(404);

        $base_url = '/api/v1/cmi/notify';
        $this->post($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Illegal parameter"
            ]);

        $post_data = ['oid' => $this->transactions->t_transaction_id];
        $this->post($base_url, $post_data);
        $this->response->assertStatus(400)
            ->assertJson([
                "status" => "fail",
                "error" => "P0006",
                "message" => "Undefined index: amount"
            ]);

        $responses[] = $this->getFormGroupResponse();
        $responses[] = $this->getPaymentAction();
        $this->createSimpleMockResponse($responses);
        $post_data = [
            'amount'   => $this->amount,
            'currency' => $this->transactions->t_currency,
            'oid'      => $this->transactions->t_transaction_id,
            'TransId'  => '995271901',
            'Response' => 'Approved',
            'ProcReturnCode' => '00'
        ];
        $post_data['HASH'] = $this->getHash($post_data);
        $this->post($base_url, $post_data);
        $this->response->assertStatus(200);
    }

    private function getHash($post_data) {
        $storeKey = env('APP_ENV') === 'production' ? env('ENVPAY_CMI_BEmaAll2be_STOREKEY') : env('ENVPAY_CMI_BEmaAll2be_SANDBOX_STOREKEY');
        $params_kyes = array_keys($post_data);
        natcasesort($params_kyes);
        $hash_val = '';
        foreach ($params_kyes as $item) {
            $paramValue        = trim($post_data[$item]);
            $escapedParamValue = str_replace("|", "\\|", str_replace("\\", "\\\\", $paramValue));
            if (strtolower($item) != "hash" && strtolower($item) != "encoding") {
                $hash_val = $hash_val . $escapedParamValue . "|";
            }
        }
        $escapedStoreKey     = str_replace("|", "\\|", str_replace("\\", "\\\\", $storeKey));
        $hash_val            = $hash_val . $escapedStoreKey;
        $calculatedHashValue = hash('sha512', $hash_val);
        return base64_encode(pack('H*', $calculatedHashValue));
    }
}
