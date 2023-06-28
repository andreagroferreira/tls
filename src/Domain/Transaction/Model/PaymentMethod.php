<?php

namespace Src\Domain\Transaction\Model;

use DateTime;

class PaymentMethod
{
    /**
     * @var null|string
     */
    private string $currency;
    /**
     * @var null|string
     */
    private string $paymentMethod;

    /**
     * @var null|string
     */
    private string $gateway;

    /**
     * @var null|string
     */
    private string $gatewayTransactionId;

    /**
     * @var null|string
     */
    private string $gatewayTransactionReference;

    /**
     * @var null|string
     */
    private string $gatewayAccount;
    /**
     * @var null|string
     */
    private string $gatewaySubAccount;

    /**
     * @var null|DateTime
     */
    private DateTime $gatewayExpirationDate;

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     */
    public function setPaymentMethod(string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @return string
     */
    public function getGateway(): string
    {
        return $this->gateway;
    }

    /**
     * @param string $gateway
     */
    public function setGateway(string $gateway): void
    {
        $this->gateway = $gateway;
    }

    /**
     * @return string
     */
    public function getGatewayTransactionId(): string
    {
        return $this->gatewayTransactionId;
    }

    /**
     * @param string $gatewayTransactionId
     */
    public function setGatewayTransactionId(string $gatewayTransactionId): void
    {
        $this->gatewayTransactionId = $gatewayTransactionId;
    }

    /**
     * @return string
     */
    public function getGatewayTransactionReference(): string
    {
        return $this->gatewayTransactionReference;
    }

    /**
     * @param string $gatewayTransactionreference
     */
    public function setGatewayTransactionReference(string $gatewayTransactionreference): void
    {
        $this->gatewayTransactionReference = $gatewayTransactionreference;
    }

    /**
     * @return string
     */
    public function getGatewayAccount(): string
    {
        return $this->gatewayAccount;
    }

    /**
     * @param string $gatewayAccount
     */
    public function setGatewayAccount(string $gatewayAccount): void
    {
        $this->gatewayAccount = $gatewayAccount;
    }

    /**
     * @return string
     */
    public function getGatewaySubAccount(): string
    {
        return $this->gatewaySubAccount;
    }

    /**
     * @param string $gatewaySubAccount
     */
    public function setGatewaySubAccount(string $gatewaySubAccount): void
    {
        $this->gatewaySubAccount = $gatewaySubAccount;
    }

    /**
     * @return DateTime
     */
    public function getGatewayExpirationDate(): DateTime
    {
        return $this->gatewayExpirationDate;
    }

    /**
     * @param DateTime $gatewayExpirationDate
     */
    public function setGatewayExpirationDate(DateTime $gatewayExpirationDate): void
    {
        $this->gatewayExpirationDate = $gatewayExpirationDate;
    }
}
