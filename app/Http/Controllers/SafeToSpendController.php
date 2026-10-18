<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SafeToSpend\SafeToSpendService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SafeToSpendController extends Controller
{
    public function show(Request $request, SafeToSpendService $service): View
    {
        $user = $request->user();
        $timezone = $user->timezone ?? 'Asia/Jakarta';

        $customDateParam = $request->query('planning_date');
        $customDate = $customDateParam
            ? CarbonImmutable::parse((string) $customDateParam, $timezone)
            : null;

        $result = $service->calculate($user, $customDate);

        return view('pages.safe-to-spend.show', [
            'result' => $result,
        ]);
    }
}
