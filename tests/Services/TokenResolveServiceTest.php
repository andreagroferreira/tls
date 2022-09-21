<?php

namespace Tests\Services;

use App\Services\TokenResolveService;
use App\Services\DirectusService;
use App\Services\ApiService;

class TokenResolveServiceTest extends \TestCase
{

    private array $template = [
            0 => [
                'code' => 'ww',
                'translation' => [
                    0 => ['email_content' => 'Dear {{a:f_pers_surnames}}, this is test address for email content : {{c:application_centers:address}} ','invoice_content'=>'This is test address for invoice content : {{c:application_centers:address}}']
                ]
            ]
        ];

    private array $expectedResult = ["email_content" => "Dear Applicant1, Applicant2, this is test address for email content : Leman building, rue du lac Leman, Berges du lac 1, 1053, Tunis, Tunisia ","invoice_content" => "This is test address for invoice content : Leman building, rue du lac Leman, Berges du lac 1, 1053, Tunis, Tunisia"];

    private array $mockResult =  [
            0 => [
                'code' => 'TUN',
                'translation' => [
                    0 => ['address' => 'Leman building, rue du lac Leman, Berges du lac 1, 1053, Tunis, Tunisia']
                ]
            ]
        ];

    private array $mockApiResult = [
            'status' => 200,
            'body' => [
                0 => [
                    'f_pers_surnames' => 'Applicant1'
                ],
                1 => [
                    'f_pers_surnames' => 'Applicant2'
                ]
            ]
        ];

    private array $filters =  [
            'code' => [
                'in' => ['tn','TUN','ww'],
            ],
            'status' => [
                'eq' => 'published',
            ],
        ];

    private string $issuer = 'tnTUN2de';

    private array $options = ['lang' => 'en-us'];

    private string $fg_id = '13512';

    private string $client = 'de';
        

    public function testTemplateIsNull()
    {
        $template = [];
        $expectedResult = [];
        $once = 0;
        $mockResult = [];
        $mockDirectusService = $this->mockDirectusService($mockResult, $this->filters, $this->options,$once);
        $mockApiService = $this->mockApiService($this->mockApiResult, $this->fg_id);

        $tokenResolveService = New TokenResolveService($mockDirectusService, $mockApiService);

        $this->assertEquals($expectedResult,$tokenResolveService->resolveTemplate($template, $this->issuer, $this->options['lang'], $this->fg_id));

    }

    public function testIssuerIsNull()
    {

        $mockResult = [];

        $issuer = '';

        $filters = [
            'code' => [
                'in' => ['ww'],
            ],
            'status' => [
                'eq' => 'published',
            ],
        ];

        $once = 1;
        $mockDirectusService = $this->mockDirectusService($mockResult, $filters, $this->options, $once);
        $mockApiService = $this->mockApiService($this->mockApiResult, $this->fg_id);

        $tokenResolveService = New TokenResolveService($mockDirectusService,$mockApiService);
        $this->expectExceptionMessage('No collections returned for token: application_centers.translation.address');
        $tokenResolveService->resolveTemplate($this->template,$issuer,$this->options['lang'],$this->fg_id);

    }

     public function testLangIsNull()
    {

        $lang = '';

        $options['lang'] = $lang;
        $once = 1;
        $mockDirectusService = $this->mockDirectusService($this->mockResult, $this->filters, $options, $once);
        $mockApiService = $this->mockApiService($this->mockApiResult, $this->fg_id);

        $tokenResolveService = New TokenResolveService($mockDirectusService, $mockApiService);

        $this->assertEquals($this->expectedResult,$tokenResolveService->resolveTemplate($this->template, $this->issuer, $lang, $this->fg_id));
    }

     public function testFormGroupIdIsNull()
    {

        $fg_id = '';
        $once = 1;
        $mockApiResult = ['status' => 404, 'message' => 'No Applicant found'];
        $mockDirectusService = $this->mockDirectusService($this->mockResult, $this->filters, $this->options, $once);
        $mockApiService = $this->mockApiService($mockApiResult, $fg_id);

        $tokenResolveService = New TokenResolveService($mockDirectusService, $mockApiService);

        $this->expectExceptionMessage('No applicant details returned for token: f_pers_surnames');

        $tokenResolveService->resolveTemplate($this->template, $this->issuer, $this->options['lang'], $fg_id);
    }

    public function testResolveTemplate()
    {

        $once = 1;
        $mockDirectusService = $this->mockDirectusService($this->mockResult, $this->filters, $this->options, $once);
        $mockApiService = $this->mockApiService($this->mockApiResult, $this->fg_id);

        $tokenResolveService = New TokenResolveService($mockDirectusService, $mockApiService);

        $this->assertEquals($this->expectedResult,$tokenResolveService->resolveTemplate($this->template, $this->issuer, $this->options['lang'], $this->fg_id));
    }

    private function mockDirectusService($mockResult, $filters, $options, $once): object
    {
        $collection = 'tlspay_email_invoice';
        $field = 'code,translation.address';
        $mockDirectusService = \Mockery::mock(DirectusService::class);
        if($once){
            $mockDirectusService->shouldReceive('getContent')
            ->withArgs(fn($collection, $field, $filters, $options) => true)->atLeast(1)
            ->andReturn($mockResult);
        }else{
            $mockDirectusService->shouldReceive('getContent')
            ->withArgs(fn($collection, $field, $filters, $options) => true)
            ->andReturn($mockResult);
        }

        return $this->app->instance('App\Services\DirectusService', $mockDirectusService);
    }

    private function mockApiService($mockResult, $fg_id): object
    {
        $method = 'GET';
        $url = '/tls/v2/'.$this->client.'/forms_in_group/'.$fg_id;

        $mockApiService = \Mockery::mock(ApiService::class);

        $mockApiService->shouldReceive('callTlsApi')
            ->withArgs(fn($method, $url) => true)->atLeast(1)
            ->andReturn($mockResult);

        return $this->app->instance('App\Services\ApiService', $mockApiService);
    }

}
