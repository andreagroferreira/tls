<?php


namespace App\Services;


class GatewayService
{
    private $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }
    public function getGateways($client, $issuer, $l) {
        $lang = $l ? $l : 'en';
        $all_gateway = config('payment_gateway');
        $translation = $this->translationService->getTranslation('payment', $lang);
        $data = $all_gateway[$client][$issuer] ?? [];
        foreach ($data as $payment_method => $val) {
            if ($translation[$lang][$payment_method]) {
                $data[$payment_method]['label'] = $translation[$lang][$payment_method];
            }
        }
        return $data;
    }

    public function getGateway($client, $issuer, $gateway) {
        return config('payment_gateway')[$client][$issuer][$gateway] ?? [];
    }
}
