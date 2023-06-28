<?php

namespace Src\Application\Repositories\Payment\Gateway;

use Src\Domain\Payment\Model\Gateway\Provider;
use Src\Domain\Payment\Repositories\ProviderRepositoryInterface;

class ProviderRepository implements ProviderRepositoryInterface
{
    public function getProviderById(int $providerId): bool
    {
        return true;
    }

    public function updateProviderById(int $providerId, Provider $provider): bool
    {
        return true;
    }

    public function createProvider(Provider $paymentGateway): bool
    {
        return true;
    }

    public function destroyProvider(int $providerId): bool
    {
        return true;
    }
}
