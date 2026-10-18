<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use App\Enums\SavingsGoalStatus;
use Carbon\CarbonImmutable;

final readonly class SavingsGoalData
{
    public function __construct(
        public string $name,
        public Money $targetAmount,
        public ?CarbonImmutable $targetDate = null,
        public SavingsGoalStatus $status = SavingsGoalStatus::Active,
        public string $color = '#2563EB',
        public string $icon = 'target',
        public ?int $targetAccountId = null,
        public ?string $notes = null,
    ) {}
}
