<?php

namespace App\Services;

use App\Repositories\RecommendationResultRepositories;

class AvsRecommendationService
{
    protected $client;
    protected $apiService;
    protected $refreshCache = false;
    protected $directusService;
    protected $recommendationRuleEngineService;
    protected $recommendationResultRepositories;
    private $avsItemTemplate = [
        'vat' => '0.00',
        'price' => '0.00',
        'currency' => '',
        'avs_description' => null,
        'sku_description' => null,
        'avs_sale_script' => null,
        'sku_sale_script' => null,
        'recommendation_priority' => null
    ];

    public function __construct(
        ApiService                       $apiService,
        DirectusService                  $directusService,
        DbConnectionService              $dbConnectionService,
        RecommendationRuleEngineService  $recommendationRuleEngineService,
        RecommendationResultRepositories $recommendationResultRepositories
    )
    {
        $this->client                           = $apiService->getProjectId();
        $this->apiService                       = $apiService;
        $this->directusService                  = $directusService;
        $this->recommendationRuleEngineService  = $recommendationRuleEngineService;
        $this->recommendationResultRepositories = $recommendationResultRepositories;
        $this->recommendationResultRepositories->setConnection($dbConnectionService->getConnection());
    }

    public function calcRecommendAvs($params)
    {
        $f_id   = $params['f_id'];
        $step   = $params['step'];
        $limit  = $params['limit'];
        $application_response = $this->apiService->callTlsApi('GET', 'tls/v2/' . $this->client . '/application/' . $f_id);
        if ($application_response['status'] != 200 || empty($application_response['body'])) {
            return [];
        }
        $application = $application_response['body'];
        $this->refreshCache = $params['refresh_cache'];
        $issuer_avses = $this->getIssuerAvsWithPriority($application['f_xcopy_ug_xref_i_tag']);
        $issuer_avses = array_column($issuer_avses, null, 'sku');

        //get the basket requested and paid avs from tlsconnect
        $basket_avs = $this->getBasketAvs($f_id);
        $requested_skus = array_keys($basket_avs['requested'] ?? []);
        $paid_skus = array_keys($basket_avs['paid'] ?? []);

        //get the rule engine recommendation avs
        $basket_skus = array_merge($requested_skus, $paid_skus);
        $rule_engine_avs = $this->getRecommendByRuleEngine($params, $application, $basket_skus);
        $removed_skus = array_keys($rule_engine_avs['remove']);
        $add_skus = array_keys($rule_engine_avs['add']);
        $conflict_skus = $rule_engine_avs['conflict'] ?? [];

        //get the recommendation result from recommendation result table
        $rcd_result_skus = $this->getRecommendResultSkus($f_id);

        $requested_avs = $this->getShowingAvs($basket_avs['requested'] ?? [], $issuer_avses);
        $paid_avs = $this->getShowingAvs($basket_avs['paid'] ?? [], $issuer_avses);
        $denied_skus = array_keys($rcd_result_skus['deny'] ?? []);
        $denied_avs = [];
        $all_avs = [];
        foreach($issuer_avses as $item) {
            $avs_sku = $item['sku'];
            if(in_array($avs_sku, array_keys($rcd_result_skus['deny'] ?? []))) {
                $item['rcd_id'] = $rcd_result_skus['deny'][$avs_sku]['rr_id'];
                array_push($denied_avs, $item);
            }
            if(in_array($avs_sku, $add_skus)) {
                $item['recommendation_priority'] = (int) $rule_engine_avs['add'][$avs_sku]['Priority'];
            }
            $item['is_display'] = !in_array($item['sku'], array_merge($requested_skus, $paid_skus, $denied_skus, $removed_skus));
            $item['avs_conflict'] = in_array($item['sku'], $conflict_skus);
            $item['not_recommended'] = in_array($item['sku'], $removed_skus);
            $item['not_recommended_display'] = $item['not_recommended'] && !in_array($item['sku'], array_merge($requested_skus, $paid_skus, $denied_skus));
            $item['_score'] = is_null($item['recommendation_priority']) ? 1000 : $item['recommendation_priority'];
            $item['_score'] += ($item['is_display'] ? 0 : 1000);
            unset($item['rcd_id']);
            array_push($all_avs, $item);
        }

        $count = 0;
        $all_avs = collect($all_avs)
            ->sortBy('_score')
            ->map(function($avs) use (&$count, $limit) {
                if(!is_null($avs['recommendation_priority']) && $avs['is_display']) {
                    $avs['is_recommended'] = $count < $limit;
                    $count ++;
                }
                return $avs;
            })
            ->values();

        return [
            'all_avs' => $all_avs,
            'requested_avs' => $requested_avs,
            'paid_avs' => $paid_avs,
            'denied_avs' => $denied_avs
        ];
    }

    //if no local avs recommendation priority, we use global avs recommendation priority
    // and not fetch the sales script and description in api
    private function getIssuerAvsWithPriority($issuer){
        $filters = [
            'vac.code'=>[
                'eq' => $issuer
            ],
            'status' => [
                'eq' => 'published'
            ],
            'source' => [
                'eq' => 'WEBPOS'
            ],
        ];
        $select = 'avs.sku, avs.recommendation_priority, vat,price,currency.code, recommendation_priority';
        $all_avs_infos = $this->directusService->getContent('vac_avs', $select, $filters, [], ['refreshCache' => $this->refreshCache]);
        $all_avs = [];
        foreach($all_avs_infos as $avs) {
            $all_avs[] = [
                'service_name' => null,
                'sku' => array_get($avs, 'avs.sku'),
                'vat' => number_format(array_get($avs, 'vat'), 2),
                'price' => number_format(array_get($avs, 'price'), 2),
                'currency' => array_get($avs, 'currency.code'),
                'avs_description' => null,
                'sku_description' => null,
                'avs_sale_script' => null,
                'sku_sale_script' => null,
                'recommendation_priority' => array_get($avs, 'recommendation_priority') ?: array_get($avs, 'avs.recommendation_priority')
            ];
        }
        return $all_avs;
    }

    private function getRecommendResultSkus($f_id)
    {
        $rcd_results = $this->recommendationResultRepositories->fetchByFId($f_id)->toArray();
        return collect($rcd_results)->groupBy('rr_result')->map(function ($item) {
            return $item->keyBy('rr_sku');
        })->toArray();
    }

    private function getBasketAvs($f_id)
    {
        $basket_response = $this->apiService->callTlsApi('GET', 'tls/v1/' . $this->client . '/basket/' . $f_id . '?online_avs=no');
        if ($basket_response['status'] != 200 || empty($basket_response['body'])) {
            return [];
        }
        return collect($basket_response['body'])->groupBy(function ($item) {
            if ($item['paid']) {
                return 'paid';
            } else {
                return 'requested';
            }
        })->map(function ($item) {
            return $item->keyBy('s_sku');
        })->toArray();
    }

    private function getRecommendByRuleEngine($params, $application, $basket_skus)
    {
        $stages_response = $this->apiService->callTlsApi('GET', 'tls/v1/' . $this->client . '/form_stages_statues/' . $params['f_id']);
        $stage_status = array_column($stages_response['body']['stage_status'] ?? [], 'status', 'stage');
        $condition = [
            'issuer' => $application['f_xcopy_ug_xref_i_tag'],
            'Visa Type' => $application['f_visa_type'],
            'Travel Purpose' => $application['f_trav_purpose'],
            'Age Range' => $application['f_pers_age'],
            'Nationality' => $application['f_pers_nationality'],
            'Account Type' => $application['ug_type'],
            'In a group' => null,
            'Step' => $params['step'],
            'Visa SubType (UK)' => $application['f_ext_visa_purpose'],
            'City of residence' => null
        ];
        return $this->recommendationRuleEngineService->fetchRules($condition, $stage_status, $basket_skus);
    }

    private function getShowingAvs($avses, $issuer_avses) {
        if (empty($avses)) {
            return [];
        }
        $search_skus = array_keys($issuer_avses);
        $return_avses = [];
        foreach(($avses ?? []) as $avs) {
            $sku = $avs['s_sku'];
            if(in_array($sku, $search_skus)) {
                $item = $issuer_avses[$sku];
            } else {
                $item = $this->avsItemTemplate;
                $item = array_merge($item, [
                    'service_name' => $sku,
                    'sku' => $sku
                ]);
            }
            if ($avs['paid']) {
                $item['paid_price'] = $avs['paid_price'];
            }
            $item['quantity'] = $avs['av_value'];
            $item['a_id'] = $avs['a_id'];
            array_push($return_avses, $item);
        }
        return $return_avses;
    }
}
