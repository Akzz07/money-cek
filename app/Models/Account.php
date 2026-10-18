<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\AccountStatus;
use App\Enums\AccountType;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'account_number',
        'institution_name',
        'opening_balance',
        'opening_date',
        'include_in_total_assets',
        'include_in_safe_to_spend',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'status' => AccountStatus::class,
            'opening_balance' => MoneyCast::class,
            'opening_date' => 'date',
            'include_in_total_assets' => 'boolean',
            'include_in_safe_to_spend' => 'boolean',
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
     * @return HasMany<Transaction, $this>
     */
    public function sourceTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'source_account_id');
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function destinationTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'destination_account_id');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AccountStatus::Active);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', AccountStatus::Archived);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeSpendable(Builder $query): Builder
    {
        return $query->active()->where('include_in_safe_to_spend', true);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeAssets(Builder $query): Builder
    {
        return $query->active()->where('include_in_total_assets', true);
    }
}
