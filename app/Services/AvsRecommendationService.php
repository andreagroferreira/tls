<?php

namespace App\Services;

use App\Repositories\RecommendationResultRepositories;

class AvsRecommendationService
{
    protected $client;
    protected $directusService;
    protected $recommendationResultRepositories;
    protected $apiService;

    public function __construct(
        DirectusService                  $directusService,
        ApiService                       $apiService,
        RecommendationResultRepositories $recommendationResultRepositories,
        DbConnectionService $dbConnectionService
    )
    {
        $this->client                           = env('PROJECT');
        $this->directusService                  = $directusService;
        $this->apiService                       = $apiService;
        $this->recommendationResultRepositories = $recommendationResultRepositories;
        $this->recommendationResultRepositories->setConnection($dbConnectionService->getConnection());
    }

    public function calcRecommendAvs($params)
    {
        $f_id   = $params['f_id'];
        $limit  = $params['limit'];

        //get the recommendation avs from directus
        $recommendSkus = $this->getRecommendSkus($f_id, $limit);
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

    private function getRecommendSkus($f_id, $limit)
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
}
