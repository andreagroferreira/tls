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
    private $expectedResult = ['email_content' => 'Dear Applicant1, Applicant2, this is test address for email content : Leman building, rue du lac Leman, Berges du lac 1, 1053, Tunis, Tunisia ', 'invoice_content' => 'This is test address for invoice content : Leman building, rue du lac Leman, Berges du lac 1, 1053, Tunis, Tunisia'];

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

    public function testTemplateIsEmpty()
    {
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
            'tnTUN2de',
            'en-us',
            '13512'
        );

        $this->assertEmpty($resolvedTemplate);
    }

    /**
     * @dataProvider defaultTemplate
     *
     * @param array $template
     *
     * @throws \Exception
     *
     * @return void
     */
    public function testIssuerIsEmpty(array $template)
    {
        $this->directusServiceMock
            ->expects($this->once())
            ->method('getContent')
            ->willReturn([]);

        $this->apiServiceMock
            ->expects($this->once())
            ->method('callTlsApi')
            ->willReturn($this->apiServiceMockResult);

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $this->expectExceptionMessage('No collections returned for token: application_centers.translation.address');

        $tokenResolveService->resolveTemplate(
            $template,
            '',
            'en-us',
            '13512'
        );
    }

    /**
     * @dataProvider defaultTemplate
     *
     * @param array $template
     *
     * @throws \Exception
     *
     * @return void
     */
    public function testLangIsEmpty(array $template)
    {
        $this->directusServiceMock
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($this->directusServiceMockResult);

        $this->apiServiceMock
            ->expects($this->once())
            ->method('callTlsApi')
            ->willReturn($this->apiServiceMockResult);

        $tokenResolveService = new TokenResolveService($this->directusServiceMock, $this->apiServiceMock);

        $result = $tokenResolveService->resolveTemplate(
            $template,
            'tnTUN2de',
            '',
            '13512'
        );
    }

    public function testFormGroupIdIsNull()
    {
        $fg_id = '';
        $once = 1;
        $mockApiResult = ['status' => 404, 'message' => 'No Applicant found'];
        $mockDirectusService = $this->mockDirectusService($this->mockResult, $this->filters, $this->options, $once);
        $mockApiService = $this->mockApiService($mockApiResult, $fg_id);

        $tokenResolveService = new TokenResolveService($mockDirectusService, $mockApiService);

        $this->expectExceptionMessage('No applicant details returned for token: f_pers_surnames');

        $tokenResolveService->resolveTemplate($this->template, $this->issuer, $this->options['lang'], $fg_id);
    }

    public function testResolveTemplate()
    {
        $once = 1;
        $mockDirectusService = $this->mockDirectusService($this->mockResult, $this->filters, $this->options, $once);
        $mockApiService = $this->mockApiService($this->mockApiResult, $this->fg_id);

        $tokenResolveService = new TokenResolveService($mockDirectusService, $mockApiService);

        $this->assertEquals($this->expectedResult, $tokenResolveService->resolveTemplate($this->template, $this->issuer, $this->options['lang'], $this->fg_id));
    }

    public function defaultTemplate(): array
    {
        return [
            [
                [
                    0 => [
                        'code' => 'ww',
                        'translation' => [
                            0 => ['email_content' => 'Dear {{a:f_pers_surnames}}, this is test address for email content : {{c:application_centers:address}} ', 'invoice_content' => 'This is test address for invoice content : {{c:application_centers:address}}'],
                        ],
                    ],
                ],
            ],
        ];
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
