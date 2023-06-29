<?php

namespace Src\Application\Factories\Payment\Gateway;

use Illuminate\Http\Request;
use Src\Domain\Payment\Model\Gateway\Provider;
use Src\Infrastructure\EloquentModels\ProviderEloquentModel;

class ProviderFactory
{
    public static function fromRequest(Request $request): ?Provider
    {
        $providerEloquentModel = ProviderEloquentModel::find($request->get('psp_id'));

        if (null === $providerEloquentModel) {
            return null;
        }

        return self::fromEloquent($providerEloquentModel);
    }

    public static function fromEloquent(ProviderEloquentModel $providerEloquentModel): Provider
    {
        $provider = new Provider();
        $provider->setId($providerEloquentModel->psp_id);
        $provider->setCode($providerEloquentModel->psp_code);
        $provider->setName($providerEloquentModel->psp_name);
        $provider->setDeleted($providerEloquentModel->psp_tech_deleted);

        return $provider;
    }

    public static function toEloquent(Provider $provider): ProviderEloquentModel
    {
        $providerEloquentModel = new ProviderEloquentModel();
        if ($provider->getId()) {
            $providerEloquentModel = ProviderEloquentModel::findOrFail($provider->getId());
        }

        $providerEloquentModel->psp_code = $provider->getCode();
        $providerEloquentModel->psp_name = $provider->getName();
        $providerEloquentModel->psp_tech_deleted = $provider->isDeleted();

        return $providerEloquentModel;
    }

    /**
     * @param int $providerId
     *
     * @return bool|Provider
     */
    public static function toMergeProvider(int $providerId): Provider
    {
        $providerEloquentModel = ProviderEloquentModel::find($providerId);

        if (null === $providerEloquentModel) {
            return false;
        }

        return self::fromEloquent($providerEloquentModel);
    }

}
