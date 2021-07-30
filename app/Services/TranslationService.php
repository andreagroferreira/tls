<?php


namespace App\Services;


class TranslationService
{
    protected $apiService;

    public function __construct(ApiService $apiService){
        $this->apiService = $apiService;
    }
    public function getTranslation($name, $l) {
        $content = [];
        $lang = $l ? $l : 'en';
        $url = $this->baseUrl($name, $lang);
        $output = $this->apiService->callDirectusApi('get', $url);
        $content[$lang] = $this->contentHtml($output);
        return $content;
    }

    private function contentHtml($output) {
        if ($output && $output['status'] != 200) {
            return [];
        }
        $current = current($output['body']['data']);
        return current($current['translation']);
    }
    private function baseUrl($name, $lang) {

        return "_/items/{$name}?fields[0]=*.*&filter[status][eq]=published&lang={$lang}";
    }
}
