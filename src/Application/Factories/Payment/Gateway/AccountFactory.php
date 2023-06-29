<?php

namespace Src\Application\Factories\Payment\Gateway;

use Illuminate\Http\Request;
use Src\Domain\Payment\Model\Gateway\Account;
use Src\Domain\Payment\Model\Gateway\Provider;
use Src\Infrastructure\EloquentModels\AccountEloquentModel;

class AccountFactory
{
    public static function fromRequest(Request $request): ?Account
    {
        $accountEloquentModel = AccountEloquentModel::find($request->get('pa_id'));

        if (null === $accountEloquentModel) {
            return null;
        }

        $provider = ProviderFactory::toMergeProvider($accountEloquentModel->pa_xref_psp_id);

        return self::fromEloquent($accountEloquentModel, $provider);
    }

    public static function fromEloquent(AccountEloquentModel $accountEloquentModel, Provider $provider): Account
    {
        $account = new Account($provider);
        $account->setId($accountEloquentModel->pa_id);
        $account->setType($accountEloquentModel->pa_type);
        $account->setName($accountEloquentModel->pa_name);
        $account->setConfiguration($accountEloquentModel->pa_info);

        return $account;
    }

    public static function toEloquent(Account $account): AccountEloquentModel
    {
        $accountEloquentModel = new AccountEloquentModel();
        if ($account->getId()) {
            $accountEloquentModel = AccountEloquentModel::findOrFail($account->getId());
        }

        $accountEloquentModel->pa_xref_psp_id = $account->getProvider()->getId();
        $accountEloquentModel->pa_type = $account->getType();
        $accountEloquentModel->pa_name = $account->getName();
        $accountEloquentModel->pa_info = $account->getConfiguration();

        return $accountEloquentModel;
    }

    /**
     * @param int $accountId
     *
     * @return Account|bool
     */
    public static function toMergeAccount(int $accountId): Account
    {
        $accountEloquentModel = AccountEloquentModel::find($accountId);

        if (null === $accountEloquentModel) {
            return false;
        }

        $provider = ProviderFactory::toMergeProvider($accountEloquentModel->pa_xref_psp_id);

        return self::fromEloquent($accountEloquentModel, $provider);
    }
}
