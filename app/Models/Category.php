<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'icon',
        'color',
        'is_system',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
            'status' => CategoryStatus::class,
            'is_system' => 'boolean',
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
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CategoryStatus::Active);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', CategoryStatus::Archived);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', CategoryType::Income);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', CategoryType::Expense);
    }

    /**
     * Scope categories available for a user (system categories + user-owned categories).
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForUser(Builder $query, User $user, ?CategoryType $type = null): Builder
    {
        $query->where(function (Builder $q) use ($user): void {
            $q->whereNull('user_id')
                ->orWhere('user_id', $user->id);
        });

        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query;
    }

    public function isSystem(): bool
    {
        return $this->is_system;
    }

    public function isActive(): bool
    {
        return $this->status === CategoryStatus::Active;
    }

    /**
     * @return HasMany<Budget, $this>
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * @return HasMany<Bill, $this>
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}
