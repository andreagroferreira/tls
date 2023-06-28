<?php

namespace Src\Application\Factories\Payment\Gateway;

use Src\Domain\Payment\Model\Gateway\Account;
use Src\Domain\Payment\Model\Gateway\Configuration;
use Src\Infrastructure\EloquentModels\ConfigurationEloquentModel;

class ConfigurationFactory
{
    /**
     * @param Request $request
     *
     * @return bool|Configuration
     */
    public static function fromRequest(Request $request): Configuration
    {
        $configurationEloquentModel = ConfigurationEloquentModel::find($request->get('pc_id'));

        if (null === $configurationEloquentModel) {
            return false;
        }

        $account = AccountFactory::toMergeAccount($configurationEloquentModel->pc_xref_pa_id);

        if (null === $account) {
            return false;
        }

        return self::fromEloquent($configurationEloquentModel, $account);
    }

    public static function fromEloquent(
        ConfigurationEloquentModel $configurationEloquentModel,
        Account $account
    ): Configuration {
        $configuration = new Configuration($account);
        $configuration->setId($configurationEloquentModel->pc_id);
        $configuration->setCountry($configurationEloquentModel->pc_country);
        $configuration->setCity($configurationEloquentModel->pc_city);
        $configuration->setService($configurationEloquentModel->pc_service);
        $configuration->setIsActive($configurationEloquentModel->pc_is_active);
        $configuration->setIsDeleted($configurationEloquentModel->pc_is_deleted);

        return $configuration;
    }
}
