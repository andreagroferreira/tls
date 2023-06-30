<?php

namespace Src\Domain\Payment\Model\Gateway;

class Configuration
{
    /**
     * @var int
     */
    private int $id = 1;

    /**
     * @var Account
     */
    private Account $account;

    /**
     * @var null|string
     */
    private string $country;

    /**
     * @var null|string
     */
    private string $city;

    /**
     * @var null|string
     */
    private string $service;

    /**
     * @var bool
     */
    private bool $isActive = true;

    /**
     * @var bool
     */
    private bool $isDeleted = false;

    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): int
    {
        $this->id = $id;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getService(): string
    {
        return $this->service;
    }

    public function setService(string $service): void
    {
        $this->service = $service;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): void
    {
        $this->isDeleted = $isDeleted;
    }
}
