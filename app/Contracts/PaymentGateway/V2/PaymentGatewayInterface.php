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

    /**
     * Refund a charged credit card.
     *
     * @param float  $amount
     * @param string $transactionId
     *
     * @return mixed
     */
    public function refund(float $amount, string $transactionId);

    /**
     * Cancel a pending charge.
     *
     * @param string $transactionId
     *
     * @return mixed
     */
    public function cancel(string $transactionId);
}
