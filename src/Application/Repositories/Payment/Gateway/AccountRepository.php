<?php

namespace Src\Application\Repositories\Payment\Gateway;

use Src\Domain\Payment\Model\Gateway\Account;
use Src\Domain\Payment\Repositories\AccountRepositoryInterface;

class AccountRepository implements AccountRepositoryInterface
{

    public function getAccountById(int $accountId): bool
    {
        // TODO: Implement getAccountById() method.
    }

    public function createAccount(Account $account): bool
    {
        // TODO: Implement createAccount() method.
    }

    public function updateAccountById(int $accountId, Account $account): bool
    {
        // TODO: Implement updateAccountById() method.
    }

    public function destroyAccount(int $accountId): bool
    {
        // TODO: Implement destroyAccount() method.
    }
}
