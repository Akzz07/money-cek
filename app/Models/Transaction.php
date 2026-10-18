<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\AdjustmentDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'source_account_id',
        'destination_account_id',
        'category_id',
        'type',
        'amount',
        'transaction_date',
        'description',
        'notes',
        'status',
        'adjustment_direction',
        'client_request_id',
        'voided_at',
        'void_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'adjustment_direction' => AdjustmentDirection::class,
            'amount' => MoneyCast::class,
            'transaction_date' => 'date',
            'voided_at' => 'datetime',
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
     * @return BelongsTo<Account, $this>
     */
    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::Posted);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeVoided(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::Voided);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', TransactionType::Income);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', TransactionType::Expense);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeTransfer(Builder $query): Builder
    {
        return $query->where('type', TransactionType::Transfer);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeAdjustment(Builder $query): Builder
    {
        return $query->where('type', TransactionType::Adjustment);
    }

    public function isPosted(): bool
    {
        return $this->status === TransactionStatus::Posted;
    }

    public function isVoided(): bool
    {
        return $this->status === TransactionStatus::Voided;
    }
}
