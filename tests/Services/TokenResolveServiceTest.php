<?php

namespace Tests\Services;

use App\Services\ApiService;
use App\Services\DirectusService;
use App\Services\TokenResolveService;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 *
 * @coversNothing
 */
class TokenResolveServiceTest extends \TestCase
{
    /**
     * @var array
     */
    private $directusServiceMockResult = [
        [
            'code' => 'TUN',
            'translation' => [
                [
                    'address' => 'Leman building, rue du lac Leman, Berges du lac 1, 1053, Tunis, Tunisia',
                ],
            ],
        ],
    ];

    /**
     * @var array
     */
    private $directusServiceMockResultReceipt = [
        [
            'code' => 'TUN',
            'translation' => [
                [
                    'receipt_legal_entity_tls_online' => 'TUN Legal entity',
                ],
            ],
        ],
    ];

    /**
     * @var array
     */
    private $apiServiceMockResult = [
        'status' => 200,
        'body' => [
            [
                'f_pers_surnames' => 'Applicant1',
            ],
            [
                'f_pers_surnames' => 'Applicant2',
            ],
        ],
    ];

    /**
     * @var DirectusService|MockObject
     */
    private $directusServiceMock;

    /**
     * @var ApiService|MockObject
     */
    private $apiServiceMock;

    public function setUp(): void
    {
        $this->directusServiceMock = $this->mockDirectusService();
        $this->apiServiceMock = $this->mockApiService();

        parent::setUp();
    }

    /**
     * @return void
     */
    public function testTemplateIsEmpty(): void
    {
        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => '13512',
        ];

        $this->directusServiceMock
            ->expects($this->never())
            ->method('getContent')
            ->willReturn([]);

        $this->apiServiceMock
            ->expects($this->never())
            ->method('callTlsApi')
            ->willReturn([]);

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);
        $resolvedTemplate = $tokenResolveService->resolveTemplate(
            [],
            $transaction,
            'en-us'
        );

        $this->assertEmpty($resolvedTemplate);
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function testIssuerIsEmptyForCollection(): void
    {
        $template = [
            [
                'code' => 'ww',
                'translation' => [
                    [
                        'invoice_content' => 'This is test address for invoice content : {{c:application_centers:address}}',
                    ],
                ],
            ],
        ];

        $transaction = [
            't_issuer' => '',
            't_xref_fg_id' => '13512',
            't_service' => 'tls',
        ];

        $this->directusServiceMock
            ->expects($this->once())
            ->method('getContent')
            ->willReturn([]);

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $expectedResult = [
            'invoice_content' => 'This is test address for invoice content : ',
        ];

        $resolvedTemplate = $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );

        $this->assertEquals($expectedResult, $resolvedTemplate);
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function testIsCorrectTranslationForCollection(): void
    {
        $template = [
            [
                'code' => 'tnTUN2de',
                'translation' => [
                    [
                        'invoice_content' => 'This is test address for invoice content : {{c:application_centers:address}}',
                    ],
                ],
            ],
        ];

        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => '',
            't_service' => 'tls',
        ];

        $this->directusServiceMock
            ->expects($this->once())
            ->method('getContent')
            ->willReturn([]);

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $expectedResult = [
            'invoice_content' => 'This is test address for invoice content : ',
        ];

        $resolvedTemplate = $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );

        $this->assertEquals($expectedResult, $resolvedTemplate);
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function testIsActiveTranslationForCollection(): void
    {
        $template = [
            [
                'code' => 'ww',
                'translation' => [],
            ],
        ];

        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => '',
            't_service' => 'tls',
        ];

        $this->directusServiceMock
            ->expects($this->never())
            ->method('getContent')
            ->willReturn([]);

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $resolvedTemplate = $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );

        $this->assertEmpty($resolvedTemplate);
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function testIsTokensResolvedForCollection(): void
    {
        $template = [
            [
                'code' => 'ww',
                'translation' => [
                    [
                        'invoice_content' => 'This is test address for invoice content : {{t:address}}',
                    ],
                ],
            ],
        ];

        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => '',
        ];
        $this->directusServiceMock
            ->expects($this->never())
            ->method('getContent')
            ->willReturn([]);

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $this->expectExceptionMessage('Token were not resolved');

        $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );
    }

    /**
     * @return void
     */
    public function testResolveTokensForCollection(): void
    {
        $template = [
            [
                'code' => 'ww',
                'translation' => [
                    [
                        'invoice_content' => 'This is test address for invoice content : {{c:application_centers:address}}',
                    ],
                ],
            ],
        ];

        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => '',
            't_service' => 'tls',
        ];

        $this->directusServiceMock
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($this->directusServiceMockResult);

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $expectedResult = [
            'invoice_content' => 'This is test address for invoice content : Leman building, rue du lac Leman, Berges du lac 1, 1053, Tunis, Tunisia',
        ];

        $resolvedTemplate = $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );

        $this->assertEquals($expectedResult, $resolvedTemplate);
    }

    /**
     * @return void
     */
    public function testResolveTokensForReceiptCollection(): void
    {
        $template = [
            [
                'code' => 'TUN',
                'translation' => [
                    [
                        'receipt_content' => '{{c:application_center_detail:receipt_legal_entity_tls_online}} This is test legal entity for receipt content',
                    ],
                ],
            ],
        ];

        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => '',
            't_service' => 'tls',
        ];

        $this->directusServiceMock
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($this->directusServiceMockResultReceipt);

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $expectedResult = [
            'receipt_content' => 'TUN Legal entity This is test legal entity for receipt content',
        ];

        $resolvedTemplate = $tokenResolveService->resolveReceiptTemplate(
            $template,
            $transaction,
            'en-us'
        );

        $this->assertEquals($expectedResult, $resolvedTemplate);
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function testFormGroupIdIsEmptyForApplication(): void
    {
        $template = [
            [
                'code' => 'ww',
                'translation' => [
                    [
                        'invoice_content' => 'This is test address for invoice content for {{a:f_pers_surnames}}',
                    ],
                ],
            ],
        ];

        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => '',
            't_service' => 'tls',
        ];

        $mockApiResult = ['status' => 404, 'message' => 'No Applicant found'];

        $this->apiServiceMock
            ->expects($this->once())
            ->method('callTlsApi')
            ->willReturn($mockApiResult);

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $expectedResult = [
            'invoice_content' => 'This is test address for invoice content for ',
        ];

        $resolvedTemplate = $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );

        $this->assertEquals($expectedResult, $resolvedTemplate);
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function testFormGroupIdIsInvalidForApplication(): void
    {
        $template = [
            [
                'code' => 'ww',
                'translation' => [
                    [
                        'invoice_content' => 'This is test address for invoice content for {{a:f_pers_surnames}}',
                    ],
                ],
            ],
        ];

        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => 'er',
            't_service' => 'tls',
        ];

        $mockApiResult = ['status' => 404, 'message' => 'No Applicant found'];

        $this->apiServiceMock
            ->expects($this->once())
            ->method('callTlsApi')
            ->willReturn($mockApiResult);

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $expectedResult = [
            'invoice_content' => 'This is test address for invoice content for ',
        ];

        $resolvedTemplate = $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );

        $this->assertEquals($expectedResult, $resolvedTemplate);
    }

    /**
     * @return void
     */
    public function testResolveTokensForApplication(): void
    {
        $template = [
            [
                'code' => 'ww',
                'translation' => [
                    [
                        'invoice_content' => 'This is test address for invoice content for {{a:f_pers_surnames}}',
                    ],
                ],
            ],
        ];

        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => '13512',
            't_service' => 'tls',
        ];

        $this->apiServiceMock
            ->expects($this->once())
            ->method('callTlsApi')
            ->willReturn($this->apiServiceMockResult);

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $expectedResult = [
            'invoice_content' => 'This is test address for invoice content for Applicant1, Applicant2',
        ];

        $resolvedTemplate = $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );

        $this->assertEquals($expectedResult, $resolvedTemplate);
    }

    /**
     * @return void
     */
    public function testTransactionItemsIsEmptyForBasket(): void
    {
        $template = [
            [
                'code' => 'ke',
                'translation' => [
                    [
                        'invoice_content' => 'This is test address for invoice content for {{basket:services}}',
                    ],
                ],
            ],
        ];

        $transaction = [
            't_issuer' => 'keNBO2de',
            't_currency' => 'KES',
            't_amount' => 2416,
            't_items' => [],
        ];

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $this->expectExceptionMessage('No Transaction items found');

        $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );
    }

    /**
     * @return void
     */
    public function testCollectionItemIsEmptyForBasket(): void
    {
        $template = [
            [
                'code' => 'ke',
                'translation' => [
                    [
                        'invoice_content' => 'This is test address for invoice content for {{basket:services}}',
                    ],
                ],
            ],
        ];

        $transaction = [
            't_id' => 4,
            't_xref_fg_id' => 13459,
            't_client' => 'de',
            't_issuer' => 'keNBO2de',
            't_currency' => 'KES',
            't_amount' => 2416,
            't_items' => [
                'f_id' => 13565,
                'skus' => [
                    [
                        'sku' => ' Premium Lounge',
                        'price' => 454,
                        'vat' => 1,
                        'quantity' => 1,
                    ],
                ],
            ],
        ];

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $this->expectExceptionMessage('No item found for the collection - basket');

        $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );
    }

    /**
     * @return void
     */
    public function testTokenTranslationForPurchasedServicesForBasket(): void
    {
        $template = [
            [
                'code' => 'ke',
                'translation' => [
                    [
                        'invoice_content' => 'This is test address for invoice content for {{basket:services}}',
                    ],
                ],
            ],
        ];

        $transaction = [
            't_id' => 4,
            't_xref_fg_id' => 13459,
            't_client' => 'de',
            't_issuer' => 'keNBO2de',
            't_currency' => 'KES',
            't_amount' => 2416,
            't_items' => [
                [
                    'f_id' => 13565,
                    'skus' => [
                        [
                            'sku' => ' Premium Lounge',
                            'price' => 908,
                            'vat' => 1,
                            'quantity' => 2,
                            'product_name' => ' Premium Lounge',
                        ],
                    ],
                ],
            ],
        ];

        $basketContent = [
            [
                'content' => '{{META_service_rows}}, total without tax - {{total_without_tax}}',
                'meta_tokens' => [
                    'META_service_rows' => 'Service -{{product_name}}, Qauntity - {{quantity}}, Currency - {{currency}}, price - {{price}}',
                ],
            ],
        ];

        $basketResolvedContent = 'Service - Premium Lounge, Qauntity - 2, Currency - KES, price - 908, total without tax - 898.92';

        $expectedResult = [
            'invoice_content' => 'This is test address for invoice content for ' . $basketResolvedContent,
        ];

        $this->directusServiceMock
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($basketContent);

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $actualResult = $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return MockObject
     */
    private function mockDirectusService(): MockObject
    {
        return $this->getMockBuilder(DirectusService::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject
     */
    private function mockApiService(): MockObject
    {
        return $this->getMockBuilder(ApiService::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
