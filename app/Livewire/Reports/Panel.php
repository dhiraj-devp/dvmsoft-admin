<?php

namespace App\Livewire\Reports;

use App\Services\Reports\ReportAssembler;
use App\Support\ReportPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;

class Panel extends Component
{
    use AuthorizesRequests;

    public string $section = 'overview';

    #[Url]
    public string $preset = 'this_month';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(string $section): void
    {
        abort_unless(array_key_exists($section, config('reports.sections', [])), 404);
        $this->authorize(config('reports.sections.'.$section.'.permission'));
        $this->section = $section;

        if ($this->from === '' || $this->to === '') {
            $period = ReportPeriod::resolve($this->preset, $this->from ?: null, $this->to ?: null);
            $this->from = $period->from->toDateString();
            $this->to = $period->to->toDateString();
        }
    }

    public function updatedPreset(): void
    {
        if ($this->preset !== 'custom') {
            $period = ReportPeriod::resolve($this->preset);
            $this->from = $period->from->toDateString();
            $this->to = $period->to->toDateString();
        }
    }

    public function render(ReportAssembler $assembler): View
    {
        $period = ReportPeriod::resolve($this->preset, $this->from ?: null, $this->to ?: null);

        return view('livewire.reports.panel', [
            'period' => $period,
            'report' => $assembler->build($this->section, $period, request()->user()),
            'presets' => ReportPeriod::presetOptions(),
            'canExport' => request()->user()?->hasPermission('reports.export') ?? false,
            'exportUrl' => route('reports.export', array_merge(['section' => $this->section], $period->query())),
            'meta' => config('reports.sections.'.$this->section),
        ]);
    }
}
