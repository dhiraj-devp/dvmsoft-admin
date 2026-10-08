<?php

namespace App\Livewire\Work;

use App\Enums\WorkDailyUpdateStatus;
use App\Models\WorkDailyUpdate;
use App\Services\Work\WorkGrowth;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MyWork extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $tab = 'list';

    public ?string $editingId = null;

    public ?string $readingId = null;

    public string $date = '';

    public string $accomplished = '';

    public string $pending = '';

    public string $learned = '';

    public string $growthNote = '';

    public function mount(): void
    {
        $this->authorize('viewAny', WorkDailyUpdate::class);
        $this->date = now()->toDateString();
        $this->normalizeTab();
    }

    public function openTab(string $tab): void
    {
        if ($tab === 'create') {
            if ($this->tab !== 'create') {
                $this->createToday();
            }

            return;
        }

        $this->tab = $tab;
        $this->normalizeTab();
    }

    public function createToday(): void
    {
        $this->authorize('create', WorkDailyUpdate::class);

        $this->date = now()->toDateString();
        $existing = $this->todayReport();

        if ($existing?->status?->isDone()) {
            $this->read($existing->id);
            session()->flash('status', "Today's report is already marked done.");

            return;
        }

        if ($existing) {
            $this->fillForm($existing);

            return;
        }

        $this->editingId = null;
        $this->readingId = null;
        $this->accomplished = '';
        $this->pending = '';
        $this->learned = '';
        $this->growthNote = '';
        $this->tab = 'create';
    }

    public function edit(string $id): void
    {
        $update = WorkDailyUpdate::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $this->authorize('update', $update);
        $this->readingId = null;
        $this->fillForm($update);
    }

    public function read(string $id): void
    {
        $update = WorkDailyUpdate::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $this->authorize('view', $update);
        $this->tab = 'show';
        $this->readingId = $update->id;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'date' => ['required', 'date'],
            'accomplished' => ['required', 'string', 'max:8000'],
            'pending' => ['nullable', 'string', 'max:4000'],
            'learned' => ['nullable', 'string', 'max:4000'],
            'growthNote' => ['nullable', 'string', 'max:500'],
        ]);

        $update = $this->editingId
            ? WorkDailyUpdate::query()->where('user_id', auth()->id())->find($this->editingId)
            : WorkDailyUpdate::query()
                ->where('user_id', auth()->id())
                ->whereDate('work_date', $validated['date'])
                ->first();

        $payload = [
            'accomplished' => $validated['accomplished'],
            'pending' => $validated['pending'] ?: null,
            'learned' => $validated['learned'] ?: null,
            'notes' => $validated['growthNote'] ?: null,
            'submitted_at' => now(),
            'status' => WorkDailyUpdateStatus::InReview,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'manager_stamp' => null,
        ];

        if ($update) {
            $this->authorize('update', $update);
            $update->update($payload);
        } else {
            $this->authorize('create', WorkDailyUpdate::class);
            $update = WorkDailyUpdate::query()->create([
                'user_id' => auth()->id(),
                'work_date' => $validated['date'],
                ...$payload,
            ]);
        }

        $this->editingId = null;
        $this->readingId = $update->id;
        $this->tab = 'show';
        $this->resetPage('workPage');
        session()->flash('status', 'Saved and sent for review.');
    }

    public function render(WorkGrowth $growth): View
    {
        $userId = auth()->id();
        $today = $this->todayReport();

        $reading = $this->readingId
            ? WorkDailyUpdate::query()->with('reviewer')->where('user_id', $userId)->find($this->readingId)
            : null;

        return view('livewire.work.my-work', [
            'reports' => WorkDailyUpdate::query()
                ->where('user_id', $userId)
                ->orderByDesc('work_date')
                ->paginate(12, pageName: 'workPage'),
            'growth' => $growth->forUser(auth()->user()),
            'todayReport' => $today,
            'canCreateToday' => $today === null || ! $today->status?->isDone(),
            'reading' => $reading,
        ]);
    }

    protected function todayReport(): ?WorkDailyUpdate
    {
        return WorkDailyUpdate::query()
            ->where('user_id', auth()->id())
            ->whereDate('work_date', now()->toDateString())
            ->first();
    }

    protected function fillForm(WorkDailyUpdate $update): void
    {
        $this->editingId = $update->id;
        $this->date = $update->work_date->toDateString();
        $this->accomplished = $update->accomplished ?? '';
        $this->pending = $update->pending ?? '';
        $this->learned = $update->learned ?? '';
        $this->growthNote = $update->notes ?? '';
        $this->tab = 'create';
    }

    protected function normalizeTab(): void
    {
        if ($this->tab === 'show' && $this->readingId) {
            return;
        }

        if (! in_array($this->tab, ['analytics', 'list', 'create'], true)) {
            $this->tab = 'list';
        }
    }
}
