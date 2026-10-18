<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Budgets\ArchiveBudget;
use App\Actions\Budgets\CreateBudget;
use App\Actions\Budgets\UpdateBudget;
use App\Enums\CategoryType;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Models\Budget;
use App\Models\Category;
use App\Services\Budgets\BudgetUsageService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class BudgetController extends Controller
{
    public function index(Request $request, BudgetUsageService $service): View
    {
        $overview = $service->getMonthlyBudgetOverview($request->user(), $request->query('month'));

        return view('pages.budgets.index', compact('overview'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $categories = Category::forUser($user, CategoryType::Expense)->active()->orderBy('name')->get();

        return view('pages.budgets.create', compact('categories'));
    }

    public function store(StoreBudgetRequest $request, CreateBudget $action): RedirectResponse
    {
        try {
            $action->handle($request->user(), $request->toBudgetData());

            return redirect()->route('budgets.index')->with('success', 'Anggaran berhasil dibuat!');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(Budget $budget): View
    {
        Gate::authorize('update', $budget);

        return view('pages.budgets.edit', compact('budget'));
    }

    public function update(UpdateBudgetRequest $request, Budget $budget, UpdateBudget $action): RedirectResponse
    {
        Gate::authorize('update', $budget);

        $action->handle($budget, $request->limitAmount(), $request->warningThreshold());

        return redirect()->route('budgets.index')->with('success', 'Anggaran berhasil diperbarui!');
    }

    public function destroy(Budget $budget, ArchiveBudget $action): RedirectResponse
    {
        Gate::authorize('delete', $budget);

        $action->handle($budget);

        return redirect()->route('budgets.index')->with('success', 'Anggaran berhasil diarsipkan!');
    }
}
