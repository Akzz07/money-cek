<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\DTOs\AccountData;
use App\Enums\AccountStatus;
use App\Models\Account;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CreateAccount
{
    /**
     * Create a new financial account for the user.
     */
    public function handle(User $user, AccountData $data): Account
    {
        if ($data->openingBalance->isNegative()) {
            throw new InvalidArgumentException('Saldo awal akun tidak boleh bernilai negatif.');
        }

        $today = CarbonImmutable::now($user->timezone ?? 'Asia/Jakarta')->startOfDay();
        if ($data->openingDate->startOfDay()->greaterThan($today)) {
            throw new InvalidArgumentException('Tanggal saldo awal tidak boleh berada di masa depan.');
        }

        return DB::transaction(function () use ($user, $data) {
            return Account::create([
                'user_id' => $user->id,
                'name' => $data->name,
                'type' => $data->type,
                'account_number' => $data->accountNumber,
                'institution_name' => $data->institutionName,
                'opening_balance' => $data->openingBalance,
                'opening_date' => $data->openingDate->toDateString(),
                'include_in_total_assets' => $data->includeInTotalAssets,
                'include_in_safe_to_spend' => $data->includeInSafeToSpend,
                'status' => AccountStatus::Active,
                'notes' => $data->notes,
            ]);
        });
    }
}
