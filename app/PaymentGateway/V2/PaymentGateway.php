<?php

namespace App\PaymentGateway\V2;

abstract class PaymentGateway
{
    /**
     * Charge a customer's credit card.
     *
     * @param float $amount
     * @param array $options
     *
     * @return mixed
     */
    abstract public function charge(float $amount, array $options = []);

    /**
     * Refund a charged credit card.
     *
     * @param float  $amount
     * @param string $transactionId
     *
     * @return mixed
     */
    abstract public function refund(float $amount, $transactionId);

    /**
     * Cancel a pending charge.
     *
     * @param string $transactionId
     *
     * @return mixed
     */
    abstract public function cancel($transactionId);
}
