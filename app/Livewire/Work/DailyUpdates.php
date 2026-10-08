<?php

namespace App\Livewire\Work;

use App\Models\User;
use App\Models\WorkDailyUpdate;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class DailyUpdates extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $userId = '';

    public function mount(): void
    {
        $this->authorize('viewAny', WorkDailyUpdate::class);
    }

    public function updatingUserId(): void
    {
        $this->resetPage('workUpdatesPage');
    }

    public function render(): View
    {
        $user = auth()->user();
        $canSeeTeam = $user->hasPermission('work.team.view');

        $updates = WorkDailyUpdate::query()
            ->with(['user', 'review', 'plan.items'])
            ->when(! $canSeeTeam, fn ($query) => $query->where('user_id', $user->id))
            ->when($canSeeTeam && $this->userId !== '', fn ($query) => $query->where('user_id', $this->userId))
            ->latest('work_date')
            ->paginate(15, ['*'], 'workUpdatesPage');

        return view('livewire.work.daily-updates', [
            'updates' => $updates,
            'canSeeTeam' => $canSeeTeam,
            'users' => $canSeeTeam ? User::query()->active()->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }
}
