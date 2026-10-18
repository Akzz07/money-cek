<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TransactionPosted;
use App\Models\AuditLog;

final class RecordTransactionAuditLog
{
    /**
     * Handle the event.
     */
    public function handle(TransactionPosted $event): void
    {
        $transaction = $event->transaction;

        AuditLog::create([
            'actor_id' => $event->actorId ?? $transaction->user_id,
            'action' => 'transaction.posted',
            'entity_type' => 'Transaction',
            'entity_id' => $transaction->id,
            'request_id' => $transaction->client_request_id,
            'metadata' => [
                'type' => $transaction->type->value,
                'amount' => $transaction->amount->toDecimalString(),
                'source_account_id' => $transaction->source_account_id,
                'destination_account_id' => $transaction->destination_account_id,
                'category_id' => $transaction->category_id,
                'transaction_date' => $transaction->transaction_date->toDateString(),
            ],
            'created_at' => now(),
        ]);
    }
}
