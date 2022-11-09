<?php

namespace Tests\Controllers\API\V1;

/**
 * @internal
 *
 * @covers \App\Http\Controllers\V1\RefundController
 * @covers \App\Models\RefundComms
 * @covers \App\Models\RefundItems
 * @covers \App\Models\RefundRequest
 * @covers \App\Repositories\RefundCommsRepository
 * @covers \App\Repositories\RefundItemsRepository
 * @covers \App\Repositories\RefundRequestRepository
 * @covers \App\Services\RefundService
 */
class RefundControllerTest extends TestCase
{
    /**
     * @var string
     */
    private $refundApi = 'api/v1/refund';

    /**
     * @return void
     */
    public function testTrue(): void
    {
        $this->assertTrue(true);
    }
}
