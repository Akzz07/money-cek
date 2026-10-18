<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Bills\CancelBillPayment;
use App\Actions\Bills\CreateBill;
use App\Actions\Bills\PayBill;
use App\Actions\Bills\SkipBill;
use App\Actions\Bills\UpdateBill;
use App\Enums\CategoryType;
use App\Http\Requests\PayBillRequest;
use App\Http\Requests\StoreBillRequest;
use App\Http\Requests\UpdateBillRequest;
use App\Models\Bill;
use App\Models\Category;
use App\Services\Bills\BillStatusService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class BillController extends Controller
{
    public function index(Request $request, BillStatusService $service): View
    {
        $overview = $service->getOverview($request->user(), $request->query('tab'));
        $accounts = $request->user()->accounts()->active()->orderBy('name')->get();

        return view('pages.bills.index', compact('overview', 'accounts'));
    }

    public function create(Request $request): View
    {
        $categories = Category::forUser($request->user(), CategoryType::Expense)->active()->orderBy('name')->get();

        return view('pages.bills.create', compact('categories'));
    }

    public function store(StoreBillRequest $request, CreateBill $action): RedirectResponse
    {
        try {
            $action->handle($request->user(), $request->toBillData());

            return redirect()->route('bills.index')->with('success', 'Tagihan berhasil ditambahkan!');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(Bill $bill): View
    {
        Gate::authorize('update', $bill);

        $categories = Category::forUser($bill->user, CategoryType::Expense)->active()->orderBy('name')->get();

        return view('pages.bills.edit', compact('bill', 'categories'));
    }

    public function update(UpdateBillRequest $request, Bill $bill, UpdateBill $action): RedirectResponse
    {
        Gate::authorize('update', $bill);

        try {
            $action->handle($bill, $request->toBillData());

            return redirect()->route('bills.index')->with('success', 'Tagihan berhasil diperbarui!');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function pay(PayBillRequest $request, Bill $bill, PayBill $action): RedirectResponse
    {
        Gate::authorize('update', $bill);

        $account = $request->user()->accounts()->where('id', $request->sourceAccountId())->firstOrFail();

        try {
            $action->handle($request->user(), $bill, $account, $request->notes());

            return redirect()->route('bills.index')->with('success', 'Pembayaran tagihan berhasil dicatat!');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancelPayment(Request $request, Bill $bill, CancelBillPayment $action): RedirectResponse
    {
        Gate::authorize('update', $bill);

        try {
            $action->handle($request->user(), $bill);

            return redirect()->route('bills.index')->with('success', 'Pembayaran tagihan dibatalkan, transaksi telah di-void!');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function skip(Bill $bill, SkipBill $action): RedirectResponse
    {
        Gate::authorize('update', $bill);

        try {
            $action->handle($bill);

            return redirect()->route('bills.index')->with('success', 'Tagihan berhasil dilewati!');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Bill $bill): RedirectResponse
    {
        Gate::authorize('delete', $bill);

        if ($bill->isPaid()) {
            return back()->with('error', 'Tagihan yang sudah lunas tidak dapat dihapus langsung. Batalkan pembayaran terlebih dahulu.');
        }

        $bill->delete();

        return redirect()->route('bills.index')->with('success', 'Tagihan berhasil dihapus!');
    }
}
