<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Domain\Money\Money;
use App\Enums\TransactionType;
use Carbon\CarbonImmutable;

final readonly class ParsedCommandResult
{
    /**
     * @param  array<string>  $missingFields
     */
    public function __construct(
        public TransactionType $type,
        public ?Money $amount,
        public ?int $accountId,
        public ?string $accountName,
        public ?int $categoryId,
        public ?string $categoryName,
        public ?string $description,
        public CarbonImmutable $transactionDate,
        public float $confidence,
        public string $rawText,
        public array $missingFields = [],
        public ?string $clientRequestId = null,
    ) {}

    public function isComplete(): bool
    {
        return empty($this->missingFields) && $this->amount !== null && $this->accountId !== null && $this->categoryId !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'amount' => $this->amount?->toDecimalString(),
            'amount_formatted' => $this->amount?->formatted(),
            'source_account_id' => $this->accountId,
            'source_account_name' => $this->accountName,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'description' => $this->description,
            'transaction_date' => $this->transactionDate->toDateString(),
            'confidence' => $this->confidence,
            'raw_text' => $this->rawText,
            'is_complete' => $this->isComplete(),
            'missing_fields' => $this->missingFields,
            'client_request_id' => $this->clientRequestId,
        ];
    }
}
