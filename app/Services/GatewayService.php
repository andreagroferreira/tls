<?php


namespace App\Services;


class GatewayService
{
    public function getGateways($client, $issuer) {
        $all_gateway = config('payment_gateway');
        return $all_gateway[$client][$issuer] ?? [];
    }

    public function getGateway($client, $issuer, $gateway) {
        return config('payment_gateway')[$client][$issuer][$gateway] ?? [];
    }
}
