<?php

namespace Src\Domain\Transaction\Model;

use DateTime;

class Transaction
{
    /**
     * @var int
     */
    private int $id = 1;

    /**
     * @var null|int
     */
    private int $formGroupId;

    /**
     * @var null|string
     */
    private string $transactionId;

    /**
     * @var null|string
     */
    private string $client;

    /**
     * @var null|string
     */
    private string $issuer;

    /**
     * @var null|string
     */
    private string $status;

    /**
     * @var null|string
     */
    private string $currency;

    /**
     * @var null|DateTime
     */
    private DateTime $expirationDate;

    /**
     * @var null|string
     */
    private string $workflow;

    /**
     * @var null|UrlActions
     */
    private UrlActions $urlActions;

    /**
     * @var bool
     */
    private bool $isDeleted = false;

    /**
     * @var null|string
     */
    private string $service;

    /**
     * @var null|Appointment
     */
    private Appointment $appointment;

    /**
     * @var null|PaymentMethod
     */
    private PaymentMethod $paymentMethod;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getFormGroupId(): int
    {
        return $this->formGroupId;
    }

    /**
     * @param int $formGroupId
     */
    public function setFormGroupId(int $formGroupId): void
    {
        $this->formGroupId = $formGroupId;
    }

    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * @param string $TransactionId
     */
    public function setTransactionId(string $TransactionId): void
    {
        $this->TransactionId = $TransactionId;
    }

    /**
     * @return string
     */
    public function getClient(): string
    {
        return $this->client;
    }

    /**
     * @param string $client
     */
    public function setClient(string $client): void
    {
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getIssuer(): string
    {
        return $this->issuer;
    }

    /**
     * @param string $issuer
     */
    public function setIssuer(string $issuer): void
    {
        $this->issuer = $issuer;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

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
     * @return DateTime
     */
    public function getExpirationDate(): DateTime
    {
        return $this->expirationDate;
    }

    /**
     * @param DateTime $expirationDate
     */
    public function setExpirationDate(DateTime $expirationDate): void
    {
        $this->expirationDate = $expirationDate;
    }

    /**
     * @return string
     */
    public function getWorkflow(): string
    {
        return $this->workflow;
    }

    /**
     * @param string $workflow
     */
    public function setWorkflow(string $workflow): void
    {
        $this->workflow = $workflow;
    }

    /**
     * @return UrlActions
     */
    public function getUrlActions(): UrlActions
    {
        return $this->urlActions;
    }

    /**
     * @param UrlActions $urlActions
     */
    public function setUrlActions(UrlActions $urlActions): void
    {
        $this->urlActions = $urlActions;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    /**
     * @param bool $isDeleted
     */
    public function setIsDeleted(bool $isDeleted): void
    {
        $this->isDeleted = $isDeleted;
    }

    /**
     * @return string
     */
    public function getService(): string
    {
        return $this->service;
    }

    /**
     * @param string $service
     */
    public function setService(string $service): void
    {
        $this->service = $service;
    }

    /**
     * @return Appointment
     */
    public function getAppointment(): Appointment
    {
        return $this->appointment;
    }

    /**
     * @param Appointment $appointment
     */
    public function setAppointment(Appointment $appointment): void
    {
        $this->appointment = $appointment;
    }

    /**
     * @return PaymentMethod
     */
    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    /**
     * @param PaymentMethod $paymentMethod
     */
    public function setPaymentMethod(PaymentMethod $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }
}
