<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\BillStatus;
use Carbon\CarbonImmutable;
use Database\Factories\BillFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Bill extends Model
{
    /** @use HasFactory<BillFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'amount',
        'due_date',
        'category_id',
        'paid_transaction_id',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'due_date' => 'date',
            'status' => BillStatus::class,
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
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function paidTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'paid_transaction_id');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', BillStatus::Upcoming);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', BillStatus::Paid);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeOverdue(Builder $query, CarbonImmutable|string $today): Builder
    {
        $d = is_string($today) ? $today : $today->toDateString();

        return $query->where('status', BillStatus::Upcoming)
            ->whereDate('due_date', '<', $d);
    }

    public function isOverdue(?CarbonImmutable $today = null): bool
    {
        if ($this->status !== BillStatus::Upcoming) {
            return false;
        }

        $refDate = $today ?? CarbonImmutable::now();

        return $this->due_date->startOfDay()->lessThan($refDate->startOfDay());
    }

    public function isPaid(): bool
    {
        return $this->status === BillStatus::Paid;
    }

    public function isUpcoming(): bool
    {
        return $this->status === BillStatus::Upcoming;
    }

    public function displayStatusLabel(?CarbonImmutable $today = null): string
    {
        if ($this->isPaid()) {
            return 'Lunas';
        }

        if ($this->isOverdue($today)) {
            return 'Terlambat';
        }

        if ($this->status === BillStatus::Skipped) {
            return 'Dilewati';
        }

        return 'Mendatang';
    }

    public function displayStatusColor(?CarbonImmutable $today = null): string
    {
        if ($this->isPaid()) {
            return 'emerald';
        }

        if ($this->isOverdue($today)) {
            return 'rose';
        }

        if ($this->status === BillStatus::Skipped) {
            return 'slate';
        }

        return 'amber';
    }
}
