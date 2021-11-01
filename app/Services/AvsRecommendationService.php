<?php

namespace App\Services;

use App\Repositories\RecommendationResultRepositories;

class AvsRecommendationService
{
    protected $client;
    protected $apiService;
    protected $directusService;
    protected $recommendationRuleEngineService;
    protected $recommendationResultRepositories;

    public function __construct(
        ApiService                       $apiService,
        DirectusService                  $directusService,
        DbConnectionService              $dbConnectionService,
        RecommendationRuleEngineService  $recommendationRuleEngineService,
        RecommendationResultRepositories $recommendationResultRepositories
    )
    {
        $this->client                           = 'uk';
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
        $source = $params['source'];

        $application_response = $this->apiService->callTlsApi('GET', 'tls/v2/' . $this->client . '/application/' . $f_id);
        if ($application_response['status'] != 200 || empty($application_response['body'])) {
            return [];
        }
        $application = $application_response['body'];
        $issuer_avses = $this->getIssuerAvs($application['f_xcopy_ug_xref_i_tag']);

        //get the recommendation avs
        $recommend_skus = $this->getRecommendSkus($source, $application, $issuer_avses, $step, $limit);
        //get the basket requested and paid avs from tlsconnect
        $basket_avs = $this->getBasketAvs($f_id);
        //get the recommendation result from recommendation result table
        $rcd_result_skus = $this->getRecommendResultSkus($f_id);

        $requested_avs = [];
        $paid_avs = [];
        $denied_avs = [];
        $all_avs = [];

        foreach($issuer_avses as $item) {
            $avs_sku = $item['sku'];
            $item['is_display'] = true;
            if(in_array($avs_sku, array_keys($basket_avs['requested']) ?? [])) {
                $item['quantity'] = $basket_avs['requested'][$avs_sku]['av_value'];
                array_push($requested_avs, $item);
                $item['is_display'] = false;
            }
            if(in_array($avs_sku, array_keys($basket_avs['paid'] ?? []))) {
                $item['quantity'] = $basket_avs['paid'][$avs_sku]['av_value'];
                array_push($paid_avs, $item);
                $item['is_display'] = false;
            }
            if(in_array($avs_sku, $rcd_result_skus['deny'] ?? [])) {
                array_push($denied_avs, $item);
                $item['is_display'] = false;
            }
            $item['is_recommended'] = in_array($item['sku'], $recommend_skus);
            unset($item['quantity']);
            array_push($all_avs, $item);
        }

        $all_avs = collect($all_avs)
            ->sortByDesc('recommendation_priority')
            ->sortByDesc('is_recommended')
            ->values();

        return [
            'all_avs' => $all_avs,
            'requested_avs' => $requested_avs,
            'paid_avs' => $paid_avs,
            'denied_avs' => $denied_avs
        ];
    }

    private function getIssuerAvs($issuer) {
        $filters = [
            'vac.code'=>[
                'eq' => $issuer
            ],
            'status' => [
                'eq' => 'published'
            ],
        ];
        $select = 'avs.sku,vat,price,currency.code,specific_infos.*,recommendation_priority';
        $all_avs_infos = $this->directusService->getContent('vac_avs', $select, $filters);
        $all_avs = [];
        foreach($all_avs_infos as $avs) {
            $all_avs[] = [
                'service_name' => array_get($avs, 'specific_infos.0.name')?: array_get($avs, 'avs.sku'),
                'sku' => array_get($avs, 'avs.sku'),
                'vat' => number_format(array_get($avs, 'vat'), 2),
                'price' => number_format(array_get($avs, 'price'), 2),
                'currency' => array_get($avs, 'currency.code'),
                'description' => array_get($avs, 'specific_infos.0.short_description'),
                'recommendation_priority' => array_get($avs, 'recommendation_priority')
            ];
        }
        return $all_avs;
    }

    private function getRecommendResultSkus($f_id)
    {
        $rcd_results = $this->recommendationResultRepositories->fetchByFId($f_id)->toArray();
        return collect($rcd_results)->groupBy('rr_result')->map(function ($item) {
            return collect($item)->pluck('rr_sku')->values()->toArray();
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

    private function getRecommendSkus($source, $application, $issuer_avses, $step, $limit)
    {
        switch ($source) {
            case 'directus':
                $result = $this->getRecommendByDirectus($issuer_avses, $limit);
                break;
            case 'rule_engine':
                $result = $this->getRecommendByRuleEngine( $application, $step, $limit);
                break;
            default:
                $result =[];
                break;
        }
        return $result;
    }

    private function getRecommendByDirectus($issuer_avses, $limit)
    {
        return collect($issuer_avses)
            ->sortByDesc('recommendation_priority')
            ->pluck('sku')
            ->take($limit)
            ->toArray();
    }

    private function getRecommendByRuleEngine($application, $step, $limit)
    {
        $condition = [
            'issuer' => $application['f_xcopy_ug_xref_i_tag'],
            'Visa Type' => $application['f_visa_type'],
            'Travel Purpose' => $application['f_trav_purpose'],
            'Age' => $application['f_pers_age'],
            'Nationality' => $application['f_pers_nationality'],
            'Account Type' => $application['ug_type'],
            'In a group' => null,
            'Step' => $step,
            'Visa SubType (UK)' => $application['f_ext_visa_purpose'],
            'City of residence' => null,
            'top' => $limit
        ];
        return $this->recommendationRuleEngineService->fetchRules($condition);
    }
}
