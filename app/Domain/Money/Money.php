<?php

declare(strict_types=1);

namespace App\Domain\Money;

use Brick\Math\BigDecimal;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

final readonly class Money implements JsonSerializable, Stringable
{
    private BigDecimal $amount;

    private function __construct(BigDecimal $amount)
    {
        $this->amount = $amount->toScale(RoundingPolicy::SCALE, RoundingPolicy::MODE);
    }

    /**
     * Create a new Money value object from an integer, numeric string, or BigDecimal.
     */
    public static function of(string|int|BigDecimal $amount): self
    {
        if ($amount instanceof BigDecimal) {
            return new self($amount);
        }

        if (is_int($amount)) {
            return new self(BigDecimal::of($amount));
        }

        $cleaned = self::normalizeString((string) $amount);

        try {
            return new self(BigDecimal::of($cleaned));
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Format nominal uang tidak valid: [{$amount}]", 0, $e);
        }
    }

    /**
     * Create a zero Money instance.
     */
    public static function zero(): self
    {
        return new self(BigDecimal::zero());
    }

    /**
     * Add another Money instance to this one.
     */
    public function add(self $other): self
    {
        return new self($this->amount->plus($other->amount));
    }

    public function plus(self $other): self
    {
        return $this->add($other);
    }

    /**
     * Subtract another Money instance from this one.
     */
    public function subtract(self $other): self
    {
        return new self($this->amount->minus($other->amount));
    }

    public function minus(self $other): self
    {
        return $this->subtract($other);
    }

    /**
     * Multiply this Money by a multiplier.
     */
    public function multiply(string|int|BigDecimal $multiplier): self
    {
        $factor = $multiplier instanceof BigDecimal ? $multiplier : BigDecimal::of((string) $multiplier);

        return new self($this->amount->multipliedBy($factor));
    }

    /**
     * Divide this Money by a divisor.
     */
    public function divide(string|int|BigDecimal $divisor): self
    {
        $div = $divisor instanceof BigDecimal ? $divisor : BigDecimal::of((string) $divisor);

        if ($div->isZero()) {
            throw new InvalidArgumentException('Pembagian dengan nol tidak diperbolehkan.');
        }

        return new self($this->amount->dividedBy($div, RoundingPolicy::SCALE, RoundingPolicy::MODE));
    }

    /**
     * Return the absolute value of this Money.
     */
    public function abs(): self
    {
        return new self($this->amount->abs());
    }

    /**
     * Return the negated value of this Money.
     */
    public function negated(): self
    {
        return new self($this->amount->negated());
    }

    public function isZero(): bool
    {
        return $this->amount->isZero();
    }

    public function isPositive(): bool
    {
        return $this->amount->isPositive();
    }

    public function isNegative(): bool
    {
        return $this->amount->isNegative();
    }

    public function greaterThan(self $other): bool
    {
        return $this->amount->isGreaterThan($other->amount);
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->greaterThan($other);
    }

    public function greaterThanOrEqual(self $other): bool
    {
        return $this->amount->isGreaterThanOrEqualTo($other->amount);
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        return $this->greaterThanOrEqual($other);
    }

    public function lessThan(self $other): bool
    {
        return $this->amount->isLessThan($other->amount);
    }

    public function isLessThan(self $other): bool
    {
        return $this->lessThan($other);
    }

    public function lessThanOrEqual(self $other): bool
    {
        return $this->amount->isLessThanOrEqualTo($other->amount);
    }

    public function isLessThanOrEqual(self $other): bool
    {
        return $this->lessThanOrEqual($other);
    }

    public function equals(self $other): bool
    {
        return $this->amount->isEqualTo($other->amount);
    }

    public function isEqualTo(self $other): bool
    {
        return $this->equals($other);
    }

    /**
     * Get the exact string representation formatted for SQL DECIMAL(19,2).
     * Example: "1250000.00"
     */
    public function toDecimalString(): string
    {
        return (string) $this->amount;
    }

    public function amount(): string
    {
        return $this->toDecimalString();
    }

    /**
     * Get the underlying BigDecimal instance.
     */
    public function toBigDecimal(): BigDecimal
    {
        return $this->amount;
    }

    /**
     * Format as Indonesian Rupiah string (e.g. "Rp 12.450.000").
     */
    public function formatted(bool $withDecimals = false): string
    {
        return MoneyFormatter::format($this, $withDecimals);
    }

    public function jsonSerialize(): string
    {
        return $this->toDecimalString();
    }

    public function __toString(): string
    {
        return $this->toDecimalString();
    }

    /**
     * Clean up input strings (strip "Rp", currency symbols, spacing, and normalize decimal separators).
     */
    private static function normalizeString(string $value): string
    {
        $clean = trim($value);

        // Remove "Rp" or "rp" prefix and all whitespace
        $clean = preg_replace('/^rp\.?\s*/i', '', $clean);
        $clean = str_replace([' ', "\xc2\xa0"], '', (string) $clean);

        // If string has both '.' and ',' (e.g. "12.450.000,50" or "12,450,000.50")
        if (str_contains($clean, '.') && str_contains($clean, ',')) {
            $lastDot = strrpos($clean, '.');
            $lastComma = strrpos($clean, ',');

            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                // Indonesian format: 12.450.000,50 -> dot is thousand, comma is decimal
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                // US format: 12,450,000.50 -> comma is thousand, dot is decimal
                $clean = str_replace(',', '', $clean);
            }
        } elseif (str_contains($clean, '.')) {
            // Only strip dots if there are multiple dots (e.g. "12.450.000")
            if (substr_count($clean, '.') > 1) {
                $clean = str_replace('.', '', $clean);
            }
        } elseif (str_contains($clean, ',')) {
            // If multiple commas, it's thousands separator (e.g. "12,450,000")
            if (substr_count($clean, ',') > 1) {
                $clean = str_replace(',', '', $clean);
            } else {
                // Single comma is decimal separator (e.g. "12500,50")
                $clean = str_replace(',', '.', $clean);
            }
        }

        return $clean;
    }
}
