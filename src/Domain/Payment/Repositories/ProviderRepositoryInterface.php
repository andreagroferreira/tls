<?php

namespace Src\Domain\Payment\Repositories;

use Src\Domain\Payment\Model\Gateway\Provider;

interface ProviderRepositoryInterface
{
    public function getProviderById(int $providerId): bool;

    public function createProvider(Provider $paymentGateway): bool;

    public function updateProviderById(int $providerId, Provider $provider): bool;

    public function destroyProvider(int $providerId): bool;
}
