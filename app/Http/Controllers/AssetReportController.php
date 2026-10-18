<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Reporting\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AssetReportController extends Controller
{
    public function index(Request $request, FinancialReportService $service): View
    {
        $overview = $service->getReport($request->user(), $request->query('period', 'this_month'));

        return view('pages.assets.index', compact('overview'));
    }
}
