<?php

namespace Tests\Services;

use App\Services\ApiService;
use App\Services\DirectusService;
use App\Services\TokenResolveService;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 * @coversNothing
 */
class TokenResolveServiceTest extends \TestCase
{
    private $directusServiceMockResult = [
        0 => [
            'code' => 'TUN',
            'translation' => [
                0 => ['address' => 'Leman building, rue du lac Leman, Berges du lac 1, 1053, Tunis, Tunisia'],
            ],
        ],
    ];

    private $apiServiceMockResult = [
        'status' => 200,
        'body' => [
            0 => [
                'f_pers_surnames' => 'Applicant1',
            ],
            1 => [
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
            ->willReturn([])
        ;

        $this->apiServiceMock
            ->expects($this->never())
            ->method('callTlsApi')
            ->willReturn([])
        ;

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);
        $resolvedTemplate = $tokenResolveService->resolveTemplate(
            [],
            $transaction,
            'en-us'
        );

        $this->assertEmpty($resolvedTemplate);
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function testIssuerIsEmptyForCollection(): void
    {
        $transaction = [
            't_issuer' => '',
            't_xref_fg_id' => '13512',
        ];

        $template = [
            0 => [
                'code' => 'ww',
                'translation' => [
                    0 => ['email_content' => 'This is test address for email content : {{c:application_centers:address}} ', 'invoice_content' => 'This is test address for invoice content : {{c:application_centers:address}}'],
                ],
            ],
        ];
        $this->directusServiceMock
            ->expects($this->once())
            ->method('getContent')
            ->willReturn([])
        ;

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $this->expectExceptionMessage('No collections returned for token: application_centers.translation.address');

        $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function testIsCorrectTranslationForCollection(): void
    {
        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => '',
        ];

        $template = [
            0 => [
                'code' => 'tnTUN2de',
                'translation' => [
                    0 => ['email_content' => 'This is test address for email content : {{c:application_centers:address}} ', 'invoice_content' => 'This is test address for invoice content : {{c:application_centers:address}}'],
                ],
            ],
        ];
        $this->directusServiceMock
            ->expects($this->never())
            ->method('getContent')
            ->willReturn([])
        ;

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $this->expectExceptionMessage('Correct collection index not found');

        $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function testIsActiveTranslationForCollection(): void
    {
        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => '',
        ];

        $template = [
            0 => [
                'code' => 'ww',
                'translation' => [],
            ],
        ];
        $this->directusServiceMock
            ->expects($this->never())
            ->method('getContent')
            ->willReturn([])
        ;

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $this->expectExceptionMessage('No active translation found');

        $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function testIsTokensResolvedForCollection(): void
    {
        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => '',
        ];

        $template = [
            0 => [
                'code' => 'ww',
                'translation' => [
                    0 => ['email_content' => 'This is test address for email content : {{t:address}}', 'invoice_content' => 'This is test address for invoice content : {{t:address}}'],
                ],
            ],
        ];
        $this->directusServiceMock
            ->expects($this->never())
            ->method('getContent')
            ->willReturn([])
        ;

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
        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => '',
        ];

        $template = [
            0 => [
                'code' => 'ww',
                'translation' => [
                    0 => ['email_content' => 'This is test address for email content : {{c:application_centers:address}} ', 'invoice_content' => 'This is test address for invoice content : {{c:application_centers:address}}'],
                ],
            ],
        ];
        $this->directusServiceMock
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($this->directusServiceMockResult)
        ;

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $expectedResult = [
            'email_content' => 'This is test address for email content : Leman building, rue du lac Leman, Berges du lac 1, 1053, Tunis, Tunisia ',
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
     * @throws \Exception
     *
     * @return void
     */
    public function testFormGroupIdIsEmptyForApplication(): void
    {
        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => '',
        ];

        $template = [
            0 => [
                'code' => 'ww',
                'translation' => [
                    0 => ['email_content' => 'Dear {{a:f_pers_surnames}} ,This is test address for email content ', 'invoice_content' => 'This is test address for invoice content for {{a:f_pers_surnames}}'],
                ],
            ],
        ];
        $mockApiResult = ['status' => 404, 'message' => 'No Applicant found'];

        $this->apiServiceMock
            ->expects($this->once())
            ->method('callTlsApi')
            ->willReturn($mockApiResult)
        ;

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $this->expectExceptionMessage('No applicant details returned for token: f_pers_surnames');

        $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function testFormGroupIdIsInvalidForApplication(): void
    {
        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => 'er',
        ];

        $template = [
            0 => [
                'code' => 'ww',
                'translation' => [
                    0 => ['email_content' => 'Dear {{a:f_pers_surnames}} ,This is test address for email content ', 'invoice_content' => 'This is test address for invoice content for {{a:f_pers_surnames}}'],
                ],
            ],
        ];
        $mockApiResult = ['status' => 404, 'message' => 'No Applicant found'];

        $this->apiServiceMock
            ->expects($this->once())
            ->method('callTlsApi')
            ->willReturn($mockApiResult)
        ;

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $this->expectExceptionMessage('No applicant details returned for token: f_pers_surnames');

        $tokenResolveService->resolveTemplate(
            $template,
            $transaction,
            'en-us'
        );
    }

    /**
     * @return void
     */
    public function testResolveTokensForApplication(): void
    {
        $transaction = [
            't_issuer' => 'tnTUN2de',
            't_xref_fg_id' => '13512',
        ];

        $template = [
            0 => [
                'code' => 'ww',
                'translation' => [
                    0 => ['email_content' => 'Dear {{a:f_pers_surnames}} ,This is test address for email content', 'invoice_content' => 'This is test address for invoice content for {{a:f_pers_surnames}}'],
                ],
            ],
        ];
        $mockApiResult = ['status' => 404, 'message' => 'No Applicant found'];

        $this->apiServiceMock
            ->expects($this->once())
            ->method('callTlsApi')
            ->willReturn($this->apiServiceMockResult)
        ;

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $expectedResult = [
            'email_content' => 'Dear Applicant1, Applicant2 ,This is test address for email content',
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
            0 => [
                'code' => 'ke',
                'translation' => [
                    0 => ['email_content' => 'This is test address for email content {{basket:services}}', 'invoice_content' => 'This is test address for invoice content for'],
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
            0 => [
                'code' => 'ke',
                'translation' => [
                    0 => ['email_content' => 'This is test address for email content {{basket:services}}', 'invoice_content' => 'This is test address for invoice content for'],
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
            0 => [
                'code' => 'ke',
                'translation' => [
                    0 => ['email_content' => 'This is test address for email content {{basket:services}}', 'invoice_content' => 'This is test address for invoice content for'],
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
            't_items' => [0 => [
                'f_id' => 13565,
                'skus' => [
                    0 => [
                        'sku' => ' Premium Lounge',
                        'price' => 454,
                        'vat' => 1,
                        'quantity' => 1,
                    ],
                ],
            ],
            ],
        ];

        $basketContent = [
            0 => [
                'content' => '{{META_service_rows}}, total without tax - {{total_without_tax}}',
                'meta_tokens' => [
                    'META_service_rows' => 'Service -{{sku}}, Qauntity - {{quantity}}, Currency - {{currency}}, price - {{price}}',
                ],
            ],
        ];

        $basketResolvedContent = 'Service - Premium Lounge, Qauntity - 2, Currency - KES, price - 908, total without tax - 2416.00';

        $expectedResult = [
            'email_content' => 'This is test address for email content '.$basketResolvedContent,
            'invoice_content' => 'This is test address for invoice content for',
        ];

        $this->directusServiceMock
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($basketContent)
        ;

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
            ->getMock()
        ;
    }

    /**
     * @return MockObject
     */
    private function mockApiService(): MockObject
    {
        return $this->getMockBuilder(ApiService::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }
}
