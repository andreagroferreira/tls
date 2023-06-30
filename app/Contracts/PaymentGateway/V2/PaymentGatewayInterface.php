<?php

namespace App\Contracts\PaymentGateway\V2;

interface PaymentGatewayInterface
{
    /**
     * Charge a customer's credit card.
     *
     * @param float $amount
     * @param array $options
     *
     * @return mixed
     */
    public function charge(float $amount, array $options = []);
}
