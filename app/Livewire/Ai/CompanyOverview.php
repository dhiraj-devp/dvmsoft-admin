<?php

namespace App\Livewire\Ai;

use App\Services\Ai\CompanyRiskAssistant;
use Illuminate\Contracts\View\View;

class CompanyOverview extends AiPanel
{
    public function mount(): void
    {
        $this->authorize($this->permission());
    }

    public function generateInsights(CompanyRiskAssistant $assistant): void
    {
        $user = request()->user();

        $this->generateWith(fn () => $assistant->analyze($user));
    }

    public function render(): View
    {
        return view('livewire.ai.company-overview');
    }

    protected function feature(): string
    {
        return 'overview';
    }

    protected function permission(): string
    {
        return 'ai.overview.view';
    }
}
