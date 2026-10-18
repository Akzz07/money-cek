<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\DTOs\AccountData;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

final class UpdateAccount
{
    /**
     * Update an existing financial account.
     */
    public function handle(Account $account, AccountData $data): Account
    {
        return DB::transaction(function () use ($account, $data) {
            $account->update([
                'name' => $data->name,
                'type' => $data->type,
                'account_number' => $data->accountNumber,
                'institution_name' => $data->institutionName,
                'include_in_total_assets' => $data->includeInTotalAssets,
                'include_in_safe_to_spend' => $data->includeInSafeToSpend,
                'notes' => $data->notes,
            ]);

            return $account->fresh();
        });
    }
}
