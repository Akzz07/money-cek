<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use App\Enums\AccountType;
use Carbon\CarbonImmutable;

final readonly class AccountData
{
    public function __construct(
        public string $name,
        public AccountType $type,
        public ?string $accountNumber,
        public ?string $institutionName,
        public Money $openingBalance,
        public CarbonImmutable $openingDate,
        public bool $includeInTotalAssets,
        public bool $includeInSafeToSpend,
        public ?string $notes = null,
    ) {}
}
