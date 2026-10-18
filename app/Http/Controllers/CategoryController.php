<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Categories\ArchiveCategory;
use App\Actions\Categories\CreateCategory;
use App\Actions\Categories\UpdateCategory;
use App\Enums\CategoryType;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $expenseCategories = Category::forUser($user, CategoryType::Expense)
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();

        $incomeCategories = Category::forUser($user, CategoryType::Income)
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();

        return view('pages.categories.index', compact('expenseCategories', 'incomeCategories'));
    }

    public function create(): View
    {
        return view('pages.categories.create');
    }

    public function store(StoreCategoryRequest $request, CreateCategory $action): RedirectResponse
    {
        $action->handle($request->user(), $request->toCategoryData());

        return redirect()->route('categories.index')->with('success', 'Kategori baru berhasil ditambahkan!');
    }

    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        return view('pages.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategory $action): RedirectResponse
    {
        $action->handle($category, $request->toCategoryData());

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui!');
    }

    public function destroy(Category $category, ArchiveCategory $action): RedirectResponse
    {
        Gate::authorize('delete', $category);

        $action->handle($category);

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diarsipkan!');
    }
}
