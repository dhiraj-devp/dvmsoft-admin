<?php

namespace App\Livewire\Work;

use App\Models\WorkDailyUpdate;
use App\Models\WorkReview;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Reviews extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public ?string $updateId = null;

    public array $form = [];

    public function mount(): void
    {
        $this->authorize('viewAny', WorkReview::class);
        $this->resetForm();
    }

    public function start(string $updateId): void
    {
        $update = WorkDailyUpdate::query()->findOrFail($updateId);
        $this->authorize('view', $update);
        abort_unless(auth()->user()->hasPermission('work.reviews.manage'), 403);

        $this->updateId = $update->id;
        $existing = $update->review;
        $this->form = [
            'quality_score' => (string) ($existing?->quality_score ?? '4'),
            'feedback' => $existing?->feedback ?? '',
            'action_items' => $existing?->action_items ?? '',
        ];
    }

    public function save(): void
    {
        $this->authorize('create', WorkReview::class);
        $update = WorkDailyUpdate::query()->findOrFail($this->updateId);

        $validated = $this->validate([
            'form.quality_score' => ['required', 'integer', 'min:1', 'max:5'],
            'form.feedback' => ['required', 'string', 'max:5000'],
            'form.action_items' => ['nullable', 'string', 'max:2000'],
        ]);

        WorkReview::query()->updateOrCreate(
            ['work_daily_update_id' => $update->id],
            [
                'user_id' => $update->user_id,
                'reviewer_id' => auth()->id(),
                'quality_score' => $validated['form']['quality_score'],
                'feedback' => $validated['form']['feedback'],
                'action_items' => $validated['form']['action_items'] ?: null,
                'reviewed_on' => now()->toDateString(),
            ]
        );

        $this->updateId = null;
        $this->resetForm();
        session()->flash('status', 'Review saved.');
    }

    public function render(): View
    {
        $user = auth()->user();
        $canManage = $user->hasPermission('work.reviews.manage');

        return view('livewire.work.reviews', [
            'updates' => WorkDailyUpdate::query()
                ->with(['user', 'review', 'plan.items'])
                ->when(! $canManage, fn ($query) => $query->where('user_id', $user->id))
                ->latest('work_date')
                ->paginate(15, ['*'], 'workReviewsPage'),
            'canManage' => $canManage,
            'selected' => $this->updateId ? WorkDailyUpdate::query()->with(['user', 'plan.items', 'learnings', 'evidence'])->find($this->updateId) : null,
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'quality_score' => '4',
            'feedback' => '',
            'action_items' => '',
        ];
    }
}
