<?php


namespace App\Services;


class CurrencyCodeService
{
    public function getCurrencyCode($currency) {
        return config('currency_code')[$currency] ?? [];
    }

    public function getCurrency($code) {
        $currency = '';
        $currency_code = config('currency_code');
        foreach ($currency_code as $key => $val) {
            if ($val != $code) { continue; }
            $currency = $key;
        }
        return $currency;
    }
}
