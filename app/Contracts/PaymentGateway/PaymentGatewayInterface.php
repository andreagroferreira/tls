<?php

namespace App\Contracts\PaymentGateway;

interface PaymentGatewayInterface
{
    public function getPaymentGatewayName();

    public function isSandBox();

    public function checkout();

    public function notify($params);

    public function redirto($params);

    public function return($params);
}
