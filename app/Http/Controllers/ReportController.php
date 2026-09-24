<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\Reports\ReportAssembler;
use App\Services\Reports\ReportCsvExporter;
use App\Support\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function overview(): View
    {
        $this->authorize('reports.view');

        return view('reports.show', ['section' => 'overview']);
    }

    public function sales(): View
    {
        $this->authorize('reports.sales.view');

        return view('reports.show', ['section' => 'sales']);
    }

    public function projects(): View
    {
        $this->authorize('reports.projects.view');

        return view('reports.show', ['section' => 'projects']);
    }

    public function finance(): View
    {
        $this->authorize('reports.finance.view');

        return view('reports.show', ['section' => 'finance']);
    }

    public function hr(): View
    {
        $this->authorize('reports.hr.view');

        return view('reports.show', ['section' => 'hr']);
    }

    public function support(): View
    {
        $this->authorize('reports.support.view');

        return view('reports.show', ['section' => 'support']);
    }

    public function export(
        Request $request,
        string $section,
        ReportAssembler $assembler,
        ReportCsvExporter $exporter,
        AuditLogger $audit,
    ): StreamedResponse {
        abort_unless(array_key_exists($section, config('reports.sections', [])), 404);

        $this->authorize($assembler->permission($section));
        $this->authorize('reports.export');

        $period = ReportPeriod::resolve(
            $request->string('preset')->toString() ?: 'this_month',
            $request->string('from')->toString() ?: null,
            $request->string('to')->toString() ?: null,
        );

        $report = $assembler->build($section, $period, $request->user());

        $audit->record(
            action: 'exported',
            module: 'reports',
            newValues: [
                'section' => $section,
                'preset' => $period->preset,
                'from' => $period->from->toDateString(),
                'to' => $period->to->toDateString(),
            ],
            user: $request->user(),
        );

        return $exporter->download(
            'dvmsoft-'.$section.'-report-'.$period->from->toDateString().'-'.$period->to->toDateString().'.csv',
            $period,
            $report['tables'] ?? [],
        );
    }
}
