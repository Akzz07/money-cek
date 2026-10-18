<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use Carbon\CarbonImmutable;

final readonly class TransferData
{
    public function __construct(
        public int $sourceAccountId,
        public int $destinationAccountId,
        public Money $amount,
        public CarbonImmutable $transactionDate,
        public ?string $description = null,
        public ?string $notes = null,
        public ?string $clientRequestId = null,
    ) {}
}
