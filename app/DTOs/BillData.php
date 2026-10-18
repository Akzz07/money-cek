<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use Carbon\CarbonImmutable;

final readonly class BillData
{
    public function __construct(
        public string $name,
        public Money $amount,
        public CarbonImmutable $dueDate,
        public ?int $categoryId = null,
        public ?string $notes = null,
    ) {}
}
