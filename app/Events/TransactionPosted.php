<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TransactionPosted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Transaction $transaction,
        public ?int $actorId = null,
    ) {}
}
