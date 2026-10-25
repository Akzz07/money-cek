<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AssetReportController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SafeToSpendController;
use App\Http\Controllers\SavingsGoalController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionExportController;
use App\Http\Controllers\VoiceCommandController;
/*
|--------------------------------------------------------------------------
| Web Routes - Monetra Mobile-First Financial App
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');

    // Voice Command Parsing
    Route::post('/voice/parse', [VoiceCommandController::class, 'parse'])->name('voice.parse')->middleware('throttle:30,1');

    // Alias dashboard to home for standard Laravel auth redirects
    Route::get('/dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');

    // Accounts Management
    Route::resource('accounts', AccountController::class);
    Route::post('/accounts/{account}/reconcile', [AccountController::class, 'reconcile'])->name('accounts.reconcile');

    // Categories Management
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Transactions & Transfers
    Route::get('/transactions/export', [TransactionExportController::class, 'export'])->name('transactions.export')->middleware('throttle:10,1');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::post('/transfers', [TransactionController::class, 'storeTransfer'])->name('transfers.store');
    Route::post('/transactions/{transaction}/void', [TransactionController::class, 'void'])->name('transactions.void');

    // Budgets Management
    Route::resource('budgets', BudgetController::class);

    // Bills & Upcoming Obligations
    Route::resource('bills', BillController::class);
    Route::post('/bills/{bill}/pay', [BillController::class, 'pay'])->name('bills.pay');
    Route::post('/bills/{bill}/cancel-payment', [BillController::class, 'cancelPayment'])->name('bills.cancel-payment');
    Route::post('/bills/{bill}/skip', [BillController::class, 'skip'])->name('bills.skip');

    // Savings Goals & Allocations
    Route::resource('savings-goals', SavingsGoalController::class);
    Route::post('/savings-goals/{savings_goal}/entries', [SavingsGoalController::class, 'addEntry'])->name('savings-goals.entries.store');

    // Safe-to-Spend Computation & Assumptions
    Route::get('/safe-to-spend', [SafeToSpendController::class, 'show'])->name('safe-to-spend.show');

    // Financial Reports & Asset Analytics
    Route::get('/assets', [AssetReportController::class, 'index'])->name('assets.index');
    Route::get('/reports', [AssetReportController::class, 'index'])->name('reports.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
