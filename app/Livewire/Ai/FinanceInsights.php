<?php

namespace App\Livewire\Ai;

use App\Services\Ai\FinanceInsightAssistant;
use Illuminate\Contracts\View\View;

class FinanceInsights extends AiPanel
{
    public function mount(): void
    {
        $this->authorize($this->permission());
    }

    public function generateInsights(FinanceInsightAssistant $assistant): void
    {
        $this->generateWith(fn () => $assistant->analyze());
    }

    public function render(): View
    {
        return view('livewire.ai.finance-insights');
    }

    protected function feature(): string
    {
        return 'finance';
    }

    protected function permission(): string
    {
        return 'ai.finance.use';
    }
}
