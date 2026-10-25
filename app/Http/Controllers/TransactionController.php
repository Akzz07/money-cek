<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Transactions\PostTransaction;
use App\Actions\Transactions\PostTransfer;
use App\Actions\Transactions\VoidTransaction;
use App\DTOs\TransactionFilter;
use App\Enums\CategoryType;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\StoreTransferRequest;
use App\Http\Requests\VoidTransactionRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\Transactions\TransactionQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TransactionController extends Controller
{
    public function index(Request $request, TransactionQueryService $queryService): View
    {
        $user = $request->user();
        $filter = TransactionFilter::fromRequest($request);
        $transactions = $queryService->paginate($user, $filter, 25);

        $accounts = $user->accounts()->orderBy('name')->get();
        $categories = Category::forUser($user)->orderBy('name')->get();

        return view('pages.transactions.index', compact('transactions', 'accounts', 'categories', 'filter'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        $accounts = $user->accounts()->active()->orderBy('name')->get();
        $expenseCategories = Category::forUser($user, CategoryType::Expense)->active()->orderBy('name')->get();
        $incomeCategories = Category::forUser($user, CategoryType::Income)->active()->orderBy('name')->get();

        return view('pages.transactions.create', compact('accounts', 'expenseCategories', 'incomeCategories'));
    }

    public function store(StoreTransactionRequest $request, PostTransaction $action): RedirectResponse|JsonResponse
    {
        $transaction = $action->handle($request->user(), $request->toTransactionData());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dicatat!',
                'redirect_url' => route('transactions.index'),
                'transaction_id' => $transaction->id,
            ]);
        }

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dicatat!');
    }

    public function storeTransfer(StoreTransferRequest $request, PostTransfer $action): RedirectResponse
    {
        $action->handle($request->user(), $request->toTransferData());

        return redirect()->route('transactions.index')->with('success', 'Transfer antar-akun berhasil dilakukan!');
    }

    public function void(VoidTransactionRequest $request, Transaction $transaction, VoidTransaction $action): RedirectResponse
    {
        $action->handle($request->user(), $transaction, $request->reason());

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dibatalkan (void)!');
    }
}
