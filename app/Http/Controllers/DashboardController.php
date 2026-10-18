<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardMetricsService;
use App\Services\SafeToSpend\SafeToSpendService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(
        Request $request,
        DashboardMetricsService $metricsService,
        SafeToSpendService $safeToSpendService,
    ): View {
        $metrics = $metricsService->getMetrics($request->user(), $request->query('month'));
        $safeToSpend = $safeToSpendService->calculate($request->user());

        return view('pages.home', [
            'metrics' => $metrics,
            'safeToSpend' => $safeToSpend,
        ]);
    }
}
