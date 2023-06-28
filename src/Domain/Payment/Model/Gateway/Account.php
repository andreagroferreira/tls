<?php

namespace Src\Domain\Payment\Model\Gateway;

class Account
{
    /**
     * @var int
     */
    private int $id = 1;

    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @var null|string
     */
    private string $name;

    /**
     * @var null|string
     */
    private string $type;

    /**
     * @var null|array
     */
    private array $configuration;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getProvider(): Provider
    {
        return $this->provider;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }
}
