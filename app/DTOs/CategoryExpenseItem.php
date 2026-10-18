<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use App\Models\Category;

final readonly class CategoryExpenseItem
{
    public function __construct(
        public ?Category $category,
        public string $categoryName,
        public string $color,
        public string $icon,
        public Money $amount,
        public float $percentage,
        public ?Money $previousAmount = null,
        public ?float $changePercentage = null,
    ) {}
}
