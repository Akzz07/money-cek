<?php

declare(strict_types=1);

namespace App\Services\Exports;

use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TransactionCsvExporter
{
    /**
     * Escape special characters that could trigger spreadsheet formula execution (CSV Injection).
     * Characters =, +, -, @, tab (\t), or carriage return (\r) at the start of a cell are escaped with a single quote.
     */
    public static function sanitizeCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $str = (string) $value;

        if (preg_match('/^[=+\-@\t\r]/', $str) === 1) {
            return "'".$str;
        }

        return $str;
    }

    /**
     * Generate a memory-efficient streamed CSV response for user's transactions.
     */
    public function export(
        User $user,
        ?CarbonImmutable $startDate = null,
        ?CarbonImmutable $endDate = null,
    ): StreamedResponse {
        $filename = 'monetra_transaksi_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($user, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Microsoft Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header Row
            fputcsv($handle, [
                'ID Transaksi',
                'Tanggal',
                'Jenis',
                'Kategori',
                'Akun Sumber',
                'Akun Tujuan',
                'Nominal (Rp)',
                'Status',
                'Deskripsi',
                'Catatan',
            ]);

            $query = Transaction::query()
                ->where('user_id', $user->id)
                ->with(['sourceAccount', 'destinationAccount', 'category'])
                ->orderByDesc('transaction_date')
                ->orderByDesc('id');

            if ($startDate !== null) {
                $query->where('transaction_date', '>=', $startDate->toDateString());
            }

            if ($endDate !== null) {
                $query->where('transaction_date', '<=', $endDate->toDateString());
            }

            $query->chunk(250, function ($transactions) use ($handle) {
                foreach ($transactions as $tx) {
                    $row = [
                        self::sanitizeCell($tx->id),
                        self::sanitizeCell($tx->transaction_date->toDateString()),
                        self::sanitizeCell($tx->type->label()),
                        self::sanitizeCell($tx->category?->name ?? '-'),
                        self::sanitizeCell($tx->sourceAccount?->name ?? '-'),
                        self::sanitizeCell($tx->destinationAccount?->name ?? '-'),
                        self::sanitizeCell($tx->amount->toDecimalString()),
                        self::sanitizeCell($tx->status->label()),
                        self::sanitizeCell($tx->description),
                        self::sanitizeCell($tx->notes ?? ''),
                    ];

                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
