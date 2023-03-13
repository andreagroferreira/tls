<?php

namespace App\PaymentGateway\V2;

abstract class PaymentGateway
{
    /**
     * It can be 'sandbox' or 'production'.
     *
     * @var string
     */
    protected $environment;

    /**
     * Charge a customer's credit card.
     *
     * @param float $amount
     * @param array $options
     *
     * @return mixed
     */
    abstract public function charge(float $amount, array $options = []);

    public function isSandbox(): bool
    {
        return $this->environment == 'sandbox';
    }
}
