<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Enums\AccountStatus;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

final class ArchiveAccount
{
    /**
     * Archive an account without deleting historical transactions.
     */
    public function handle(Account $account): Account
    {
        return DB::transaction(function () use ($account) {
            $account->update([
                'status' => AccountStatus::Archived,
            ]);

            return $account->fresh();
        });
    }
}
