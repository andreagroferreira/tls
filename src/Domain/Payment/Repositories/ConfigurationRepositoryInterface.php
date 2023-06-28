<?php

namespace Src\Domain\Payment\Repositories;

use Src\Domain\Payment\Model\Gateway\Configuration;

interface ConfigurationRepositoryInterface
{
    public function getConfigurationById(int $configurationId): bool;
    public function createConfiguration(Configuration $configuration): bool;
    public function updateConfigurationById(int $configurationId, Configuration $configuration): bool;
    public function destroyConfiguration(int $configurationId): bool;

}
