<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use App\Enums\TransactionType;
use Carbon\CarbonImmutable;

final readonly class TransactionData
{
    public function __construct(
        public TransactionType $type,
        public int $sourceAccountId,
        public Money $amount,
        public CarbonImmutable $transactionDate,
        public ?int $destinationAccountId = null,
        public ?int $categoryId = null,
        public ?string $description = null,
        public ?string $notes = null,
        public ?string $clientRequestId = null,
    ) {}
}
