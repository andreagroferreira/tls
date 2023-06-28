<?php

namespace Src\Domain\Payment\Model\Gateway;

class PaymentGateway
{
    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @var Account
     */
    private Account $account;

    /**
     * @var Configuration[]
     */
    private array $configuration;

    /**
     * @param Provider $provider
     * @param Account  $account
     * @param $configuration Configuration[]
     */
    public function __construct(Provider $provider, Account $account, array $configuration)
    {
        $this->provider = $provider;
        $this->account = $account;
        $this->configuration = $configuration;
    }

    public function getProvider(): Provider
    {
        return $this->provider;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}
