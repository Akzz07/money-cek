<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SavingsGoals\AddSavingsGoalEntry;
use App\Actions\SavingsGoals\CreateSavingsGoal;
use App\Actions\SavingsGoals\DeleteSavingsGoal;
use App\Actions\SavingsGoals\UpdateSavingsGoal;
use App\Enums\SavingsGoalStatus;
use App\Http\Requests\AddSavingsGoalEntryRequest;
use App\Http\Requests\StoreSavingsGoalRequest;
use App\Http\Requests\UpdateSavingsGoalRequest;
use App\Models\SavingsGoal;
use App\Services\SavingsGoals\SavingsGoalProgressService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class SavingsGoalController extends Controller
{
    public function index(Request $request, SavingsGoalProgressService $service): View
    {
        $statusParam = $request->query('status');
        $statusFilter = $statusParam ? SavingsGoalStatus::tryFrom((string) $statusParam) : null;

        $overview = $service->getOverview($request->user(), $statusFilter);
        $accounts = $request->user()->accounts()->active()->orderBy('name')->get();

        return view('pages.savings.index', compact('overview', 'accounts', 'statusParam'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', SavingsGoal::class);
        $accounts = $request->user()->accounts()->active()->orderBy('name')->get();

        return view('pages.savings.create', compact('accounts'));
    }

    public function store(StoreSavingsGoalRequest $request, CreateSavingsGoal $action): RedirectResponse
    {
        Gate::authorize('create', SavingsGoal::class);

        try {
            $action->handle($request->user(), $request->toSavingsGoalData());

            return redirect()->route('savings-goals.index')->with('success', 'Target tabungan berhasil dibuat!');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(SavingsGoal $savingsGoal, SavingsGoalProgressService $service): View
    {
        Gate::authorize('view', $savingsGoal);

        $progress = $service->getProgress($savingsGoal);
        $savingsGoal->load(['entries.linkedTransaction', 'targetAccount']);
        $accounts = request()->user()->accounts()->active()->orderBy('name')->get();

        return view('pages.savings.show', compact('savingsGoal', 'progress', 'accounts'));
    }

    public function edit(SavingsGoal $savingsGoal): View
    {
        Gate::authorize('update', $savingsGoal);
        $accounts = request()->user()->accounts()->active()->orderBy('name')->get();

        return view('pages.savings.edit', compact('savingsGoal', 'accounts'));
    }

    public function update(UpdateSavingsGoalRequest $request, SavingsGoal $savingsGoal, UpdateSavingsGoal $action): RedirectResponse
    {
        Gate::authorize('update', $savingsGoal);

        try {
            $action->handle($request->user(), $savingsGoal, $request->toSavingsGoalData());

            return redirect()->route('savings-goals.show', $savingsGoal)->with('success', 'Target tabungan berhasil diperbarui!');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(SavingsGoal $savingsGoal, DeleteSavingsGoal $action): RedirectResponse
    {
        Gate::authorize('delete', $savingsGoal);

        try {
            $action->handle(request()->user(), $savingsGoal);

            return redirect()->route('savings-goals.index')->with('success', 'Target tabungan berhasil dihapus.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function addEntry(AddSavingsGoalEntryRequest $request, SavingsGoal $savingsGoal, AddSavingsGoalEntry $action): RedirectResponse
    {
        Gate::authorize('update', $savingsGoal);

        try {
            $action->handle(
                $request->user(),
                $savingsGoal,
                $request->entryDirection(),
                $request->entryAmount(),
                $request->entryDate(),
                $request->notes(),
                $request->accountId(),
            );

            return back()->with('success', 'Mutasi target tabungan berhasil dicatat!');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
