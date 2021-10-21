<?php

namespace Tests\Controllers\API\V1;


class AvsRecommendationControllerTest extends TestCase
{
    private $test_recommend_skus = [
        "POSTAL-APPLICATION-SERVICE",
        "EXPRESS-COURIER-RETURN-OUT-OF-COUNTRY",
        "PASSPORT-PHOTOS",
        "PREMIUM-LOUNGE",
        "PRIME-TIME-APPOINTMENT",
        "FORM-FILLING-ASSISTANCE"
    ];

    private $test_basket_skus = [
        "requested" => [
            "POSTAL-APPLICATION-SERVICE"
        ],
        "paid"      => [
            "PHOTOCOPY"
        ]
    ];

    private $test_recommend_result_skus = [
        "deny"   => [
            "EXPRESS-COURIER-RETURN-OUT-OF-COUNTRY",
        ],
        "accept" => [
            "COURIER"
        ]
    ];


    public function testFetch()
    {
        putenv('PROJECT=be');

        $base_url = 'api/v1/avs_recommendation/test';
        $this->get($base_url);
        $this->response->assertStatus(400)
            ->assertJson([
                'error'   => 'params error',
                'message' => 'The f id must be an integer.'
            ]);

        $base_url = 'api/v1/avs_recommendation/10001';
        $this->get($base_url);
        $this->response->assertStatus(200);

        $avs_recommendation_service = app('\App\Services\AvsRecommendationService');
        $avs_recommendation_skus = $avs_recommendation_service->calc($this->test_recommend_skus, $this->test_basket_skus, $this->test_recommend_result_skus);
        $this->assertEquals(4, count($avs_recommendation_skus));
        $this->assertNotContains("POSTAL-APPLICATION-SERVICE", $avs_recommendation_skus);
        $this->assertNotContains("EXPRESS-COURIER-RETURN-OUT-OF-COUNTRY", $avs_recommendation_skus);
    }
}
