<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Domain\Money\Money;
use App\Enums\SavingsGoalEntryDirection;
use App\Enums\SavingsGoalStatus;
use Database\Factories\SavingsGoalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SavingsGoal extends Model
{
    /** @use HasFactory<SavingsGoalFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'target_date',
        'status',
        'color',
        'icon',
        'target_account_id',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_amount' => MoneyCast::class,
            'target_date' => 'immutable_date',
            'status' => SavingsGoalStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Optional dedicated savings account.
     *
     * @return BelongsTo<Account, $this>
     */
    public function targetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'target_account_id');
    }

    /**
     * @return HasMany<SavingsGoalEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(SavingsGoalEntry::class)->orderByDesc('entry_date')->orderByDesc('id');
    }

    /**
     * Calculate current saved amount based on loaded entries or direct query.
     */
    public function currentAmount(): Money
    {
        if ($this->relationLoaded('entries')) {
            $total = Money::zero();
            foreach ($this->entries as $entry) {
                if ($entry->direction === SavingsGoalEntryDirection::Contribution) {
                    $total = $total->plus($entry->amount);
                } else {
                    $total = $total->minus($entry->amount);
                }
            }

            return $total;
        }

        $contributions = $this->entries()
            ->where('direction', SavingsGoalEntryDirection::Contribution->value)
            ->sum('amount');

        $withdrawals = $this->entries()
            ->where('direction', SavingsGoalEntryDirection::Withdrawal->value)
            ->sum('amount');

        $net = bcsub((string) ($contributions ?? 0), (string) ($withdrawals ?? 0), 2);

        return Money::of($net);
    }

    /**
     * Calculate completion percentage.
     */
    public function percentage(?Money $current = null): float
    {
        $current ??= $this->currentAmount();
        $targetDecimal = (float) $this->target_amount->toDecimalString();

        if ($targetDecimal <= 0.0) {
            return 0.0;
        }

        $currentDecimal = (float) $current->toDecimalString();
        $percent = ($currentDecimal / $targetDecimal) * 100.0;

        return round($percent, 1);
    }

    /**
     * Remaining amount to reach target (zero if already reached or exceeded).
     */
    public function remainingAmount(?Money $current = null): Money
    {
        $current ??= $this->currentAmount();

        if ($current->isGreaterThanOrEqual($this->target_amount)) {
            return Money::zero();
        }

        return $this->target_amount->minus($current);
    }

    /**
     * Explicit surplus amount when current saved exceeds target.
     */
    public function surplusAmount(?Money $current = null): ?Money
    {
        $current ??= $this->currentAmount();

        if ($current->isGreaterThan($this->target_amount)) {
            return $current->minus($this->target_amount);
        }

        return null;
    }

    /**
     * Check if goal has reached or exceeded target.
     */
    public function isAchieved(?Money $current = null): bool
    {
        $current ??= $this->currentAmount();

        return $current->isGreaterThanOrEqual($this->target_amount);
    }

    /**
     * Scope for active goals.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SavingsGoalStatus::Active->value);
    }

    /**
     * Scope for completed goals.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', SavingsGoalStatus::Completed->value);
    }
}
