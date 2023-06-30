<?php

namespace Src\Domain\Payment\Model\Gateway;

class Provider
{
    /**
     * @var id
     */
    private int $id = 1;

    /**
     * @var null|string
     */
    private string $code;

    /**
     * @var null|string
     */
    private string $name;

    /**
     * @var null|bool
     */
    private bool $deleted = false;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }
}
