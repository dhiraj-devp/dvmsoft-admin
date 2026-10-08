<?php

namespace App\Livewire\Work;

use App\Enums\WorkDailyUpdateStatus;
use App\Enums\WorkManagerStamp;
use App\Models\WorkDailyUpdate;
use App\Services\Work\WorkGrowth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TeamProgress extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $status = 'in_review';

    /** @var list<string> */
    public array $selected = [];

    public ?string $readingId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canViewWorkTeam(), 403);
    }

    public function updatingStatus(): void
    {
        $this->selected = [];
        $this->readingId = null;
        $this->resetPage('teamWorkPage');
    }

    public function read(string $id): void
    {
        $update = $this->findVisible($id);
        $this->authorize('view', $update);
        $this->readingId = $update->id;
    }

    public function closeRead(): void
    {
        $this->readingId = null;
    }

    public function markDone(string $id, ?string $stamp = null): void
    {
        $update = $this->findVisible($id);
        $this->authorize('markDone', $update);
        $this->complete($update, $stamp);
        $this->selected = array_values(array_filter($this->selected, fn (string $item) => $item !== $id));

        if ($this->readingId === $id) {
            $this->readingId = null;
        }

        session()->flash('status', 'Marked as done.');
    }

    public function markSelectedDone(): void
    {
        $this->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['required', 'string'],
        ]);

        $memberIds = auth()->user()->workTeamMembers()->pluck('id');
        $marked = 0;

        foreach ($this->selected as $id) {
            $update = WorkDailyUpdate::query()->with('user')->find($id);

            if (! $update || ! $memberIds->contains($update->user_id)) {
                continue;
            }

            $this->authorize('markDone', $update);
            $this->complete($update);
            $marked++;
        }

        $this->selected = [];
        $this->readingId = null;
        session()->flash('status', $marked === 1 ? '1 report marked as done.' : $marked.' reports marked as done.');
    }

    public function selectVisible(): void
    {
        $this->selected = $this->reviews()
            ->reject(fn (WorkDailyUpdate $update) => $update->status->isDone())
            ->pluck('id')
            ->all();
    }

    #[On('confirmed-delete-work-update')]
    public function delete(string $id): void
    {
        $update = $this->findVisible($id);
        $this->authorize('delete', $update);

        if ($this->readingId === $id) {
            $this->readingId = null;
        }

        $this->selected = array_values(array_filter($this->selected, fn (string $item) => $item !== $id));
        $update->forceDelete();
        session()->flash('status', 'Report deleted.');
    }

    public function render(WorkGrowth $growth): View
    {
        $viewer = auth()->user();
        $people = $viewer->workTeamMembers()->orderBy('name')->get();
        $memberIds = $people->pluck('id');
        $snapshots = $growth->forUsers($people);
        $reviews = $this->reviews();
        $missingToday = $people
            ->whereNotIn('id', WorkDailyUpdate::query()
                ->whereIn('user_id', $memberIds)
                ->whereDate('work_date', now()->toDateString())
                ->pluck('user_id'))
            ->count();

        return view('livewire.work.team-progress', [
            'reviews' => $reviews,
            'reading' => $this->readingId ? $this->findVisible($this->readingId) : null,
            'people' => $people->map(fn ($person) => [
                'user' => $person,
                'growth' => $snapshots->get($person->id),
            ]),
            'missingToday' => $missingToday,
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, WorkDailyUpdate>
     */
    protected function reviews(): LengthAwarePaginator
    {
        $memberIds = auth()->user()->workTeamMembers()->pluck('id');

        return WorkDailyUpdate::query()
            ->with(['user', 'reviewer'])
            ->whereIn('user_id', $memberIds)
            ->when(
                $this->status !== 'all',
                fn ($query) => $query->where('status', $this->status ?: WorkDailyUpdateStatus::InReview->value)
            )
            ->orderByDesc('work_date')
            ->orderBy('user_id')
            ->paginate(12, pageName: 'teamWorkPage');
    }

    protected function findVisible(string $id): WorkDailyUpdate
    {
        $update = WorkDailyUpdate::query()->with(['user', 'reviewer'])->findOrFail($id);

        abort_unless($this->isOnTeam($update), 403);

        return $update;
    }

    protected function isOnTeam(WorkDailyUpdate $update): bool
    {
        return auth()->user()->workTeamMembers()->whereKey($update->user_id)->exists();
    }

    protected function complete(WorkDailyUpdate $update, ?string $stamp = null): void
    {
        if ($update->status?->isDone()) {
            return;
        }

        $update->update([
            'status' => WorkDailyUpdateStatus::Done,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'manager_stamp' => filled($stamp) ? WorkManagerStamp::tryFrom($stamp) : null,
        ]);
    }
}
