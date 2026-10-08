<?php

namespace App\Livewire\Work;

use App\Enums\WorkGoalStatus;
use App\Models\User;
use App\Models\WorkDailyUpdate;
use App\Models\WorkGoal;
use App\Services\Work\WorkProgressScore;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Reports extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('work.reports.view'), 403);
    }

    public function render(WorkProgressScore $scores): View
    {
        $from = now()->subDays(13)->toDateString();
        $to = now()->toDateString();

        $users = User::query()->active()->orderBy('name')->get();
        $scored = $users->map(fn (User $user) => [
            'user' => $user,
            'score' => $scores->forUser($user),
        ]);

        return view('livewire.work.reports', [
            'updatesThisPeriod' => WorkDailyUpdate::query()->whereBetween('work_date', [$from, $to])->count(),
            'openGoals' => WorkGoal::query()->whereIn('status', [
                WorkGoalStatus::NotStarted->value,
                WorkGoalStatus::InProgress->value,
                WorkGoalStatus::Blocked->value,
            ])->count(),
            'overdueGoals' => WorkGoal::query()
                ->whereIn('status', [
                    WorkGoalStatus::NotStarted->value,
                    WorkGoalStatus::InProgress->value,
                    WorkGoalStatus::Blocked->value,
                ])
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
            'scored' => $scored,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
