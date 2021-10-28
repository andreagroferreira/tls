<?php

namespace Tests\Controllers\API\V1;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class InvoiceControllerTest extends TestCase
{
    public function testFetch()
    {
        $transaction = $this->generateTransaction();

        $this->get('api/v1/invoice/' . $transaction->t_transaction_id);
        dd($this->response);
        $this->response->assertStatus(404);

        $this->updateTable('transactions', ['t_id' => $transaction->t_id], ['t_status' => 'done', 't_tech_modification' => $this->getDbNowTime()]);
        $this->get('api/v1/invoice/' . $transaction->t_transaction_id);
        $this->response->assertStatus(404);

        $file = UploadedFile::fake()->create('invoice.pdf', str_random(100), 'application/pdf');
        Storage::fake(config('payment_gateway.invoice_disk'))->putFileAs($transaction->t_client . '/' . $transaction->t_xref_fg_id, $file, $transaction->t_transaction_id . '.pdf');

        $this->get('api/v1/invoice/' . $transaction->t_transaction_id);
        $this->response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=' . $transaction->t_transaction_id . '.pdf');
    }
}
