<?php

namespace App\Services\Reports;

use App\Support\ReportPeriod;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportCsvExporter
{
    /**
     * @param  list<array{title: string, headers: list<string>, export?: list<list<mixed>>, rows?: list<list<mixed>>}>  $tables
     */
    public function download(string $filename, ReportPeriod $period, array $tables): StreamedResponse
    {
        return response()->streamDownload(function () use ($period, $tables): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Period', $period->label(), $period->from->toDateString(), $period->to->toDateString()]);
            fputcsv($handle, []);

            foreach ($tables as $table) {
                fputcsv($handle, [$table['title']]);
                fputcsv($handle, $table['headers']);

                $rows = $table['export'] ?? $table['rows'] ?? [];
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }

                fputcsv($handle, []);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
