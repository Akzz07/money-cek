<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\BudgetStatus;
use Carbon\CarbonImmutable;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'period_start',
        'period_end',
        'limit_amount',
        'warning_threshold',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'limit_amount' => MoneyCast::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'warning_threshold' => 'integer',
            'status' => BudgetStatus::class,
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
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', BudgetStatus::Active);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForPeriod(Builder $query, CarbonImmutable|string $date): Builder
    {
        $d = is_string($date) ? $date : $date->toDateString();

        return $query->whereDate('period_start', '<=', $d)
            ->whereDate('period_end', '>', $d);
    }

    public function isActive(): bool
    {
        return $this->status === BudgetStatus::Active;
    }
}
