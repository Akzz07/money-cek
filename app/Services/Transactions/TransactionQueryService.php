<?php

declare(strict_types=1);

namespace App\Services\Transactions;

use App\DTOs\TransactionFilter;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class TransactionQueryService
{
    /**
     * Paginate transactions for a user according to the provided filter.
     */
    public function paginate(User $user, TransactionFilter $filter, int $perPage = 25): LengthAwarePaginator
    {
        $query = $user->transactions()
            ->with(['category', 'sourceAccount', 'destinationAccount'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        if ($filter->search !== null && $filter->search !== '') {
            $sanitized = addcslashes($filter->search, '%_\\');
            $term = "%{$sanitized}%";
            $query->where(function (Builder $sub) use ($term) {
                $sub->whereRaw('description LIKE ? ESCAPE ?', [$term, '\\'])
                    ->orWhereRaw('notes LIKE ? ESCAPE ?', [$term, '\\']);
            });
        }

        if ($filter->accountId !== null) {
            $query->where(function (Builder $sub) use ($filter) {
                $sub->where('source_account_id', $filter->accountId)
                    ->orWhere('destination_account_id', $filter->accountId);
            });
        }

        if ($filter->categoryId !== null) {
            $query->where('category_id', $filter->categoryId);
        }

        if ($filter->type !== null) {
            $query->where('type', $filter->type);
        }

        if ($filter->startDate !== null) {
            $query->where('transaction_date', '>=', $filter->startDate->toDateString());
        }

        if ($filter->endDate !== null) {
            $query->where('transaction_date', '<=', $filter->endDate->toDateString());
        }

        if ($filter->status !== null) {
            $query->where('status', $filter->status);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
