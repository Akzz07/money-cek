<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\CategoryType;

final readonly class CategoryData
{
    public function __construct(
        public string $name,
        public CategoryType $type,
        public string $icon,
        public string $color,
    ) {}
}
