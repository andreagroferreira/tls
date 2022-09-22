<?php

namespace Tests\Controllers\API\V1;

use App\Services\InvoiceService;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \App\Http\Controllers\V1\InvoiceController
 * @covers \App\Services\InvoiceService
 *
 * @internal
 */
class InvoiceControllerTest extends TestCase
{
    /**
     * @var string
     */
    private $invoiceApi = 'api/v1/invoice';

    public function testInvoiceApiMethodIsGet(): void
    {
        $this->post($this->invoiceApi.'/1234');
        $this->response->assertStatus(405);
    }

    public function testTransactionStatusIsNotDone(): void
    {
        $transaction = $this->generateTransaction();

        $this->get($this->invoiceApi.'/'.$transaction->t_transaction_id);
        $this->response->assertStatus(404);
    }

    public function testTransactionIdNotFound(): void
    {
        $this->get($this->invoiceApi.'/1234');
        $this->response->assertStatus(404);
    }

    public function testInvoiceDownload(): void
    {
        $transaction = $this->generateTransaction();

        $this->updateTable('transactions', ['t_id' => $transaction->t_id], ['t_status' => 'done', 't_tech_modification' => $this->getDbNowTime()]);

        $mockInvoiceService = $this->mockInvoiceService();
        $mockInvoiceService->method('getInvoiceFileContent')
            ->willReturn('success');

        $this->get($this->invoiceApi.'/'.$transaction->t_transaction_id);
        $this->response->assertStatus(200);
    }

    public function testInvoiceFilePathForFileLibrary(): void
    {
        $transaction = $this->generateTransaction();

        $country = substr($transaction->t_issuer, 0, 2);
        $city = substr($transaction->t_issuer, 2, 3);
        $expectedFilePath = 'invoice/WW/'.$country.'/'.$city.'/'.$transaction->t_xref_fg_id.'/'.$transaction->t_transaction_id.'.pdf';

        $filePath = $this->getFilePath((array) $transaction);

        $this->assertEquals($expectedFilePath, $filePath);
    }

    public function testInvoiceFilePathForS3(): void
    {
        $transaction = $this->generateTransaction([
            't_xref_fg_id' => 10000,
            't_transaction_id' => str_random(10),
            't_client' => 'be',
            't_issuer' => 'dzALG2be',
            't_gateway_transaction_id' => str_random(10),
            't_gateway' => 'cmi',
            't_currency' => 'MAD',
            't_status' => 'pending',
            't_redirect_url' => 'onSuccess_tlsweb_url?lang=fr-fr',
            't_onerror_url' => 'onError_tlsweb_url?lang=fr-fr',
            't_reminder_url' => 'callback_to_send_reminder?lang=fr-fr',
            't_callback_url' => 'receipt_url/{fg_id}?lang=fr-fr',
            't_workflow' => 'vac',
            't_invoice_storage' => 's3',
        ]);

        $expectedFilePath = $transaction->t_client.'/'.$transaction->t_xref_fg_id.'/'.$transaction->t_transaction_id.'.pdf';

        $filePath = $this->getFilePath((array) $transaction);

        $this->assertEquals($expectedFilePath, $filePath);
    }

    /**
     * @return MockObject
     */
    private function mockInvoiceService(): MockObject
    {
        $mockInvoiceService = $this->getMockBuilder(InvoiceService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->app->instance(InvoiceService::class, $mockInvoiceService);

        return $mockInvoiceService;
    }

    private function getFilePath(array $transaction): string
    {
        $mockInvoiceService = $this->mockInvoiceService();

        // set protected method getFilePath() accessible and return filePath.
        $invoiceReflectionClass = new \ReflectionClass(InvoiceService::class);
        $method = $invoiceReflectionClass->getMethod('getFilePath');
        $method->setAccessible(true);

        return $method->invokeArgs($mockInvoiceService, [$transaction, $transaction['t_invoice_storage']]);
    }
}
