<?php

namespace App\Livewire\TicketSla;

use App\Enums\TicketPriority;
use App\Models\TicketSlaRule;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public array $rules = [];

    public function mount(): void
    {
        $this->authorize('viewAny', TicketSlaRule::class);

        foreach (TicketPriority::cases() as $priority) {
            $rule = TicketSlaRule::query()->firstOrCreate(
                ['priority' => $priority->value],
                [
                    'hours' => (int) config('support.sla.'.$priority->value.'.hours', 24),
                    'warning_hours' => (int) config('support.sla.'.$priority->value.'.warning_hours', 1),
                ]
            );
            $this->rules[$priority->value] = [
                'id' => $rule->id,
                'hours' => $rule->hours,
                'warning_hours' => $rule->warning_hours,
            ];
        }
    }

    public function save(): void
    {
        $this->authorize('tickets.manage_sla');

        $this->validate([
            'rules.*.hours' => ['required', 'integer', 'min:1', 'max:720'],
            'rules.*.warning_hours' => ['required', 'integer', 'min:0', 'max:168'],
        ]);

        foreach ($this->rules as $priority => $values) {
            $rule = TicketSlaRule::query()->findOrFail($values['id']);
            $this->authorize('update', $rule);
            $rule->update([
                'hours' => (int) $values['hours'],
                'warning_hours' => (int) $values['warning_hours'],
            ]);
        }

        $this->dispatch('notify', type: 'success', message: 'SLA rules saved.');
    }

    public function render(): View
    {
        return view('livewire.ticket-sla.index', [
            'priorities' => TicketPriority::cases(),
        ]);
    }
}
