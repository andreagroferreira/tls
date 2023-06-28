<?php

namespace Src\Application\Repositories\Payment\Gateway;

use Src\Domain\Payment\Model\Gateway\Configuration;
use Src\Domain\Payment\Repositories\ConfigurationRepositoryInterface;

class ConfigurationRepository implements ConfigurationRepositoryInterface
{


    public function getConfigurationById(int $configurationId): bool
    {
        // TODO: Implement getConfigurationById() method.
    }

    public function createConfiguration(Configuration $configuration): bool
    {
        // TODO: Implement createConfiguration() method.
    }

    public function updateConfigurationById(int $configurationId, Configuration $configuration): bool
    {
        // TODO: Implement updateConfigurationById() method.
    }

    public function destroyConfiguration(int $configurationId): bool
    {
        // TODO: Implement destroyConfiguration() method.
    }
}
