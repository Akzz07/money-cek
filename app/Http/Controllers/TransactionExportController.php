<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Exports\TransactionCsvExporter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TransactionExportController extends Controller
{
    /**
     * Stream secure CSV export of transactions with formula injection protection.
     */
    public function export(Request $request, TransactionCsvExporter $exporter): StreamedResponse
    {
        $timezone = $request->user()->timezone ?? 'Asia/Jakarta';

        $startDate = $request->filled('start_date')
            ? CarbonImmutable::parse((string) $request->query('start_date'), $timezone)->startOfDay()
            : null;

        $endDate = $request->filled('end_date')
            ? CarbonImmutable::parse((string) $request->query('end_date'), $timezone)->endOfDay()
            : null;

        return $exporter->export($request->user(), $startDate, $endDate);
    }
}
