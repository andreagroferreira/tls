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
        $this->client                           = env('PROJECT');
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

        //get the recommendation avs
        $recommendSkus = $this->getRecommendSkus($source, $f_id, $step, $limit);
        //get the basket requested and paid avs from tlsconnect
        $basketSkus = $this->getBasketSkus($f_id);
        //get the recommendation result from recommendation result table
        $recommendResultSkus = $this->getRecommendResultSkus($f_id);

        return $this->calc($recommendSkus, $basketSkus, $recommendResultSkus);
    }

    public function calc($recommendSkus, $basketSkus, $recommendResultSkus)
    {
        $exceptSkus = array_merge(
            $basketSkus['paid'] ?? [],
            $basketSkus['requested'] ?? [],
            $recommendResultSkus['deny'] ?? [],
        );
        return array_values(array_diff($recommendSkus, $exceptSkus));
    }

    private function getRecommendResultSkus($f_id)
    {
        $rcd_results = $this->recommendationResultRepositories->fetchByFId($f_id)->toArray();
        return collect($rcd_results)->groupBy('rr_result')->map(function ($item) {
            return collect($item)->pluck('rr_sku')->values()->toArray();
        })->toArray();
    }

    private function getBasketSkus($f_id)
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
            return collect($item)->pluck('s_sku')->values()->toArray();
        })->toArray();
    }

    private function getRecommendSkus($source, $f_id, $step, $limit)
    {
        switch ($source) {
            case 'directus':
                $result = $this->getRecommendByDirectus($f_id, $limit);
                break;
            case 'rule_engine':
                $result = $this->getRecommendByRuleEngine($f_id, $step, $limit);
                break;
            default:
                $result =[];
                break;
        }
        return $result;
    }

    private function getRecommendByDirectus($f_id, $limit)
    {
        $form_response = $this->apiService->callTlsApi('GET', 'tls/v2/' . $this->client . '/form/' . $f_id);
        if ($form_response['status'] != 200 || empty($form_response['body'])) {
            return [];
        }
        $issuer        = $form_response['body']['f_xcopy_ug_xref_i_tag'];
        $filters       = [
            'vac.code' => ['eq' => $issuer],
            'status'   => ['eq' => 'published'],
        ];
        $options       = [
            'sort'  => '-recommendation_priority',
            'limit' => $limit
        ];
        $recommend_avs = $this->directusService->getContent('vac_avs', 'avs.sku', $filters, $options);
        return collect($recommend_avs)->pluck('avs.sku')->values()->toArray();
    }

    private function getRecommendByRuleEngine($f_id, $step, $limit)
    {
        $application_response = $this->apiService->callTlsApi('GET', 'tls/v2/' . $this->client . '/application/' . $f_id);
        if ($application_response['status'] != 200 || empty($application_response['body'])) {
            return [];
        }
        $application = $application_response['body'];
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
