<?php

namespace Tests\Controllers\API\V1;

/**
 * @internal
 *
 * @covers \App\Http\Controllers\V1\RefundController
 * @covers \App\Models\Refund
 * @covers \App\Models\RefundItem
 * @covers \App\Models\RefundLog
 * @covers \App\Repositories\RefundItemRepository
 * @covers \App\Repositories\RefundLogRepository
 * @covers \App\Repositories\RefundRepository
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
