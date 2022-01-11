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

        $responses = [
            [
                'code' => 200,
                'body' => [
                  "f_id"=> 10003,
                  "f_xref_fg_id"=> 10007,
                  "f_tech_creation"=> "2015-12-04 07=>17=>48.199847+01",
                  "f_tech_modification"=> "2017-02-24 08=>47=>08+01",
                  "f_tech_deleted"=> false,
                  "f_pers_surnames"=> "SDASDDSDD",
                  "f_pers_givennames"=> "JHJ",
                  "f_pers_birth_date"=> "1989-12-15",
                  "f_pers_nationality"=> "hr",
                  "f_pass_type"=> null,
                  "f_pass_num"=> "AD2121212",
                  "f_pers_occupation"=> "Other",
                  "f_pers_addr_is_owned"=> null,
                  "f_fami_marital_status"=> "Single",
                  "f_pers_mobile_phone"=> "18612341234",
                  "f_pers_own_properties"=> null,
                  "f_comment"=> null,
                  "f_visa_type"=> "short_stay",
                  "f_trav_purpose"=> "tourisim_family",
                  "f_trav_go_to_domtom"=> false,
                  "f_is_anonymised"=> false,
                  "f_place"=> null,
                  "f_pers_age"=> 27,
                  "f_pers_province"=> null,
                  "f_pers_local_name"=> "JHJ",
                  "f_trav_departure_date"=> "2017-05-04",
                  "f_pers_sex"=> "M",
                  "f_csl"=> null,
                  "f_pers_hukou_province"=> null,
                  "f_xcopy_ug_type"=> "INDI",
                  "f_ems_waybill"=> null,
                  "f_trav_host_name"=> null,
                  "f_trav_host_address"=> "dsadasd\n\n\n\n\ndsdasdsad\n\nbe\n\n545454545\n\n",
                  "f_trav_local_host_name"=> null,
                  "f_trav_local_host_address"=> null,
                  "f_trav_local_host_phone"=> null,
                  "f_trav_arrival_date"=> "2017-05-09",
                  "f_trav_insurance_begin_date"=> null,
                  "f_trav_insurance_end_date"=> null,
                  "f_xcopy_ug_xref_i_tag"=> "tnTUN2be",
                  "f_env_color"=> "#",
                  "f_pers_csl_ref"=> null,
                  "f_comment_external"=> null,
                  "f_birth_surnames"=> null,
                  "f_birth_place"=> "DASDASD",
                  "f_birth_country"=> "bo",
                  "f_birth_nationality"=> "xxa",
                  "f_pass_issued_by"=> "tn",
                  "f_national_id"=> "DSD454545",
                  "f_identity_type"=> "ordinary_passport",
                  "f_order"=> null,
                  "f_status"=> "done",
                  "f_departure_date"=> null,
                  "f_is_activated"=> true,
                  "f_inactivation_date"=> null,
                  "f_inactivation_reason_action_id"=> null,
                  "f_inactivation_reason_message"=> null,
                  "f_cai"=> "10003",
                  "f_app_form_template"=> "application_form_template_cs",
                  "f_ext_visa_purpose"=> null,
                  "f_france_visas_reference"=> null,
                  "fi_appointment_type"=> "normal",
                  "fi_csl_appointment_type"=> "normal",
                  "fi_application_uniqueid"=> "00000048",
                  "fi_cela_id"=> null,
                  "fi_vow_ref"=> "WRWERWERWE",
                  "f_data_source"=> null,
                  "f_is_purged"=> false,
                  "fi_former_surnames"=> "",
                  "fi_guardian_surnames"=> "DASDASD",
                  "fi_guardian_givennames"=> "DADSAD",
                  "fi_guardian_address"=> "\n\n\n\n\ndasdasd\n\n\n\n\n\n",
                  "fi_guardian_nationality"=> "tn",
                  "fi_passport_issue_date"=> "2012-12-04",
                  "fi_passport_expiry_date"=> "2019-12-12",
                  "fi_residence_other_country"=> "f",
                  "fi_home_address"=> "\n\n\n\n\nsdsdasd\n\ntn\n\n\n\n",
                  "fi_trav_purpose"=> "tourism",
                  "fi_trav_purpose_other"=> "DASDASD",
                  "fi_trav_first_entry"=> "ch",
                  "fi_trav_main_dest"=> "be",
                  "fi_trav_host_type"=> "person",
                  "fi_trav_host_givennames"=> "TEST",
                  "fi_trav_host_surname"=> "TEST",
                  "fi_first_schengen_trip"=> "f",
                  "fi_eu_family_exists"=> "f",
                  "fi_eu_family_surnames"=> "",
                  "fi_eu_family_givennames"=> "",
                  "fi_eu_family_birth_date"=> "",
                  "fi_eu_family_nationality"=> "",
                  "fi_eu_family_pass_num"=> "",
                  "fi_eu_family_pass_issuing_country"=> "",
                  "fi_eu_family_relationship"=> "",
                  "fi_num_of_entries"=> "single_entry",
                  "fi_sponsorship"=> "by_host",
                  "fi_means_of_support"=> "cash",
                  "fi_fingerprints_collected"=> "f",
                  "fi_visa_number"=> "",
                  "fi_final_destination_permit_issued_by"=> "",
                  "fi_final_destination_permit_start_date"=> "",
                  "fi_final_destination_permit_end_date"=> "",
                  "fi_pers_occupation_area"=> "Architect",
                  "fi_employer_name"=> "RAWR",
                  "fi_employer_address"=> "\n\n\n\n\nrwar\n\ntn\n\n\n\n",
                  "fi_duration_of_stay"=> "12",
                  "ug_type" => "INDI"
                ],
            ],
            [
                'code' => 200,
                'body' => [
                    'data' => [
                        [
                            'avs'                     => [
                                'sku' => 'PREMIUM-LOUNGE',
                                'translation' => []
                            ],
                            'price'                   => 500,
                            'currency'                => [
                                'code' => 'XAF'
                            ],
                            'vat'                     => 0,
                            'recommendation_priority' => 6,
                            'specific_infos'          => [
                                [
                                    'id' => 358,
                                    'status' => 'draft',
                                    'created_by' => 54,
                                    'created_on' => '2021-07-02 07:41:12',
                                    'modified_by' => 11,
                                    'modified_on' => '2021-07-14 14:58:48',
                                    'language' => 'en-us',
                                    'vac_avs' => 638,
                                    'name' => 'Premium Lounge',
                                    'short_description' => 'Upgrade to experience the ultimate in convenience, comfort, and support for your application in a dedicated space with fewer queues. ',
                                    'long_description' => 'Upgrade to experience the ultimate in convenience, comfort, and support for your application in a dedicated space with fewer queues.Upgrading to our Premium Lounge gives you the space and comfort to submit your visa application in an exclusive and spacious environment without queues. You’ll enjoy privacy and a selection of complimentary refreshments while receiving one-to-one assistance and support from one of our experienced agents. With the added convenience of a flexible booking, and the opportunity to submit any missing documents later in the day, upgrade to Premium Lounge today to experience the ultimate in comfort and peace of mind.To purchase this service, please Contact Us.',
                                    'video_url' => null
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            [
                'code' => 200,
                'body' => [
                    [
                        "av_key"=> "r_other_S_prime_time",
                        "av_value"=> "1",
                        "f_id"=> 10003,
                        "fg_id"=> 10007,
                        "f_cai"=> "10003",
                        "a_id"=> 1,
                        "a_occurence"=> 1,
                        "a_what"=> "application_support_requested",
                        "e_currency"=> "TND",
                        "s_price"=> "99.00",
                        "s_sku"=> "PREMIUM-LOUNGE",
                        "avs_id"=> "r_other_S_prime_time_17249",
                        "avs_title"=> "Prime Time Appointment",
                        "paid"=> false
                    ],
                 ],
            ],
            [
                'code' => 200,
                'body' => [
                ],
            ],
        ];
        $this->createSimpleMockResponse($responses);

        $base_url = 'api/v1/avs_recommendation/10003?step=Welcome';
        $this->get($base_url);
        $this->response->assertStatus(200);
        $this->response->assertJsonStructure(['all_avs', 'requested_avs', 'paid_avs', 'denied_avs']);
        $this->response->assertJsonFragment([
            'all_avs' => [
                [
                    "_score" => 1006,
                    "avs_conflict" => false,
                    "service_name" => null,
                    "sku" => "PREMIUM-LOUNGE",
                    "vat" => "0.00",
                    "price" => "500.00",
                    "currency" => "XAF",
                    "avs_description" => null,
                    "sku_description" => null,
                    "avs_sale_script" => null,
                    "sku_sale_script" => null,
                    "recommendation_priority" => 6,
                    'is_display' => false
                ]
            ]
        ]);
        $this->response->assertJsonFragment([
            'requested_avs' => [
                [
                    "a_id" => 1,
                    "service_name" => null,
                    "sku" => "PREMIUM-LOUNGE",
                    "vat" => "0.00",
                    "price" => "500.00",
                    "quantity" => "1",
                    "currency" => "XAF",
                    "avs_description" => null,
                    "recommendation_priority" => 6,
                    "sku_description" => null,
                    "avs_sale_script" => null,
                    "sku_sale_script" => null
                ]
            ]
        ]);
    }
}
