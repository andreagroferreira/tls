<?php


namespace App\Services;

use Illuminate\Support\Facades\Cache;

class DirectusService
{
    protected $apiService;

    public function __construct(ApiService $apiService){
        $this->apiService = $apiService;
    }

    public function getContent($item, $filed, $filters, $options = [], $cacheAttr = []) {
        if (isset($cacheAttr['cacheKey']) && isset($cacheAttr['refreshCache'])) {
            if (Cache::has($cacheAttr['cacheKey']) && !$cacheAttr['refreshCache']) {
                return Cache::get($cacheAttr['cacheKey']);
            }
        }
        $queryParams = [
            'fields' => $filed,
            'filter' => $filters
        ];
        if($options) {
            $queryParams = array_merge($queryParams, $options);
        }
        $url = "_/items/{$item}?" . http_build_query($queryParams);
        $result = $this->apiService->callDirectusApi('get', $url);
        if($result && $result['status'] != 200) {
            return [];
        }
        if (isset($cacheAttr['cacheKey']) && isset($cacheAttr['refreshCache'])) {
            Cache::put($cacheAttr['cacheKey'], $result['body']['data'], 15 * 60);
        }
        return $result['body']['data'];
    }

    public function getAvsWithServiceName($issuer, $lang) {
        $options['lang'] = empty($lang) ? 'en-us' : $lang;
        $all_avs_infos = $this->getAllAvs($issuer, $options);
        $all_avs = [];
        foreach($all_avs_infos as $avs) {
            $key = $avs['avs']['sku'];
            $all_avs[$key] = [
                'issuer' => $avs['vac']['code'],
                'sku' => $avs['avs']['sku'],
                'service_name' => current($avs['specific_infos'])['name'] ?: $avs['avs']['sku']
            ];
        }
        return $all_avs;
    }

    private function getAllAvs($issuer, $options = []) {
        $filters = [
            'vac.code'=>[
                'eq' => $issuer
            ],
            'status' => [
                'eq' => 'published'
            ],
        ];
        return $this->getContent('vac_avs', 'vac.code,avs.sku,specific_infos.*', $filters, $options);
    }
}
