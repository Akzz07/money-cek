<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\SavingsGoalEntryDirection;
use Database\Factories\SavingsGoalEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SavingsGoalEntry extends Model
{
    /** @use HasFactory<SavingsGoalEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'savings_goal_id',
        'user_id',
        'direction',
        'amount',
        'entry_date',
        'notes',
        'linked_transaction_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'entry_date' => 'immutable_date',
            'direction' => SavingsGoalEntryDirection::class,
        ];
    }

    /**
     * @return BelongsTo<SavingsGoal, $this>
     */
    public function savingsGoal(): BelongsTo
    {
        return $this->belongsTo(SavingsGoal::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function linkedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'linked_transaction_id');
    }
}
