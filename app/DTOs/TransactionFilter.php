<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\TransactionType;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final readonly class TransactionFilter
{
    public function __construct(
        public ?string $search = null,
        public ?int $accountId = null,
        public ?int $categoryId = null,
        public ?TransactionType $type = null,
        public ?CarbonImmutable $startDate = null,
        public ?CarbonImmutable $endDate = null,
        public ?string $status = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $type = null;
        if ($request->filled('type')) {
            $type = TransactionType::tryFrom((string) $request->input('type'));
        }

        $startDate = null;
        if ($request->filled('start_date')) {
            try {
                $startDate = CarbonImmutable::parse((string) $request->input('start_date'))->startOfDay();
            } catch (\Throwable) {
                $startDate = null;
            }
        }

        $endDate = null;
        if ($request->filled('end_date')) {
            try {
                $endDate = CarbonImmutable::parse((string) $request->input('end_date'))->endOfDay();
            } catch (\Throwable) {
                $endDate = null;
            }
        }

        return new self(
            search: $request->filled('search') ? trim((string) $request->input('search')) : null,
            accountId: $request->filled('account_id') ? (int) $request->input('account_id') : null,
            categoryId: $request->filled('category_id') ? (int) $request->input('category_id') : null,
            type: $type,
            startDate: $startDate,
            endDate: $endDate,
            status: $request->filled('status') ? (string) $request->input('status') : null,
        );
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== null
            || $this->accountId !== null
            || $this->categoryId !== null
            || $this->type !== null
            || $this->startDate !== null
            || $this->endDate !== null
            || $this->status !== null;
    }
}
