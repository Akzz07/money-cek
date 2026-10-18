<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Accounts\ArchiveAccount;
use App\Actions\Accounts\CreateAccount;
use App\Actions\Accounts\ReconcileAccount;
use App\Actions\Accounts\UpdateAccount;
use App\Http\Requests\ReconcileAccountRequest;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Services\Accounts\AccountBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class AccountController extends Controller
{
    public function index(Request $request, AccountBalanceService $balanceService): View
    {
        $accounts = $request->user()->accounts()->orderBy('status')->orderBy('name')->get();
        $balances = $balanceService->getBalancesForAccounts($accounts);
        $totalNetWorth = $balanceService->getTotalNetWorth($request->user());

        return view('pages.accounts.index', compact('accounts', 'balances', 'totalNetWorth'));
    }

    public function create(): View
    {
        return view('pages.accounts.create');
    }

    public function store(StoreAccountRequest $request, CreateAccount $action): RedirectResponse
    {
        $action->handle($request->user(), $request->toAccountData());

        return redirect()->route('accounts.index')->with('success', 'Akun keuangan berhasil ditambahkan!');
    }

    public function edit(Request $request, Account $account): View
    {
        Gate::authorize('update', $account);

        return view('pages.accounts.edit', compact('account'));
    }

    public function update(UpdateAccountRequest $request, Account $account, UpdateAccount $action): RedirectResponse
    {
        $action->handle($account, $request->toAccountData($account));

        return redirect()->route('accounts.index')->with('success', 'Akun keuangan berhasil diperbarui!');
    }

    public function destroy(Request $request, Account $account, ArchiveAccount $action): RedirectResponse
    {
        Gate::authorize('delete', $account);

        $action->handle($account);

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil diarsipkan!');
    }

    public function reconcile(ReconcileAccountRequest $request, Account $account, ReconcileAccount $action): RedirectResponse
    {
        $action->handle($request->user(), $account, $request->realBalance(), $request->reason());

        return redirect()->route('accounts.index')->with('success', 'Rekonsiliasi saldo akun berhasil dicatat!');
    }
}
