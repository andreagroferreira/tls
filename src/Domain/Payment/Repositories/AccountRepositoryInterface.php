<?php

namespace Src\Domain\Payment\Repositories;

use Src\Domain\Payment\Model\Gateway\Account;

interface AccountRepositoryInterface
{
    public function getAccountById(int $accountId): bool;
    public function createAccount(Account $account): bool;
    public function updateAccountById(int $accountId, Account $account): bool;
    public function destroyAccount(int $accountId): bool;

}
