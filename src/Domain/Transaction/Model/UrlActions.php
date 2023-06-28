<?php

namespace Src\Domain\Transaction\Model;

class UrlActions
{
    /**
     * @var null|string
     */
    private string $redirect;

    /**
     * @var null|string
     */
    private string $callback;

    /**
     * @var null|string
     */
    private string $reminder;

    /**
     * @var null|string
     */
    private string $onError;

    public function getRedirect(): string
    {
        return $this->redirect;
    }

    public function setRedirect(string $redirect): void
    {
        $this->redirect = $redirect;
    }

    public function getCallback(): string
    {
        return $this->callback;
    }

    public function setCallback(string $callback): void
    {
        $this->callback = $callback;
    }

    public function getReminder(): string
    {
        return $this->reminder;
    }

    public function setReminder(string $reminder): void
    {
        $this->reminder = $reminder;
    }

    public function getOnError(): string
    {
        return $this->onError;
    }

    public function setOnError(string $onError): void
    {
        $this->onError = $onError;
    }
}
