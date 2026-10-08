<?php

namespace App\Services\Work;

use App\Enums\WorkGoalStatus;
use App\Enums\WorkPlanItemStatus;
use App\Models\User;
use App\Models\WorkDailyPlanItem;
use App\Models\WorkDailyUpdate;
use App\Models\WorkGoal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WorkAttentionService
{
    public function __construct(protected WorkProgressScore $scores) {}

    /**
     * @return list<array{severity: string, title: string, detail: string, user_id: string, user_name: string}>
     */
    public function items(?Carbon $asOf = null): array
    {
        $asOf = $asOf ?? now();
        $users = User::query()->active()->orderBy('name')->get();
        $items = collect();

        foreach ($users as $user) {
            $items = $items->concat($this->forUser($user, $asOf));
        }

        return $items->values()->all();
    }

    /**
     * @return Collection<int, array{severity: string, title: string, detail: string, user_id: string, user_name: string}>
     */
    public function forUser(User $user, ?Carbon $asOf = null): Collection
    {
        $asOf = $asOf ?? now();
        $alerts = collect();

        $overdue = WorkGoal::query()
            ->where('assigned_user_id', $user->id)
            ->whereIn('status', [
                WorkGoalStatus::NotStarted->value,
                WorkGoalStatus::InProgress->value,
                WorkGoalStatus::Blocked->value,
            ])
            ->whereDate('due_date', '<', $asOf->toDateString())
            ->count();

        if ($overdue > 0) {
            $alerts->push($this->alert($user, 'danger', 'Overdue', "{$user->name} has {$overdue} overdue ".($overdue === 1 ? 'goal' : 'goals').'.'));
        }

        $score = $this->scores->forUser($user, $asOf);
        $threshold = (int) config('work.progress.low_score_threshold', 70);

        if ($score->enoughData && $score->percent !== null && $score->percent < $threshold) {
            $alerts->push($this->alert($user, 'warning', 'Low progress', "{$user->name}'s progress score is {$score->percent}%."));
        }

        $yesterday = $asOf->copy()->subDay();
        if ($yesterday->isWeekday()) {
            $hasUpdate = WorkDailyUpdate::query()
                ->where('user_id', $user->id)
                ->whereDate('work_date', $yesterday->toDateString())
                ->exists();

            if (! $hasUpdate) {
                $alerts->push($this->alert($user, 'warning', 'Missing update', "{$user->name} has no daily update for ".$yesterday->format('d M').'.'));
            }
        }

        $blocked = WorkDailyPlanItem::query()
            ->where('status', WorkPlanItemStatus::Blocked->value)
            ->whereHas('plan', fn ($query) => $query->where('user_id', $user->id)->where('work_date', '>=', $asOf->copy()->subDays(3)->toDateString()))
            ->count();

        if ($blocked > 0) {
            $alerts->push($this->alert($user, 'danger', 'Blocked work', "{$user->name} has {$blocked} blocked plan ".($blocked === 1 ? 'item' : 'items').'.'));
        }

        return $alerts;
    }

    /**
     * @return array{severity: string, title: string, detail: string, user_id: string, user_name: string}
     */
    protected function alert(User $user, string $severity, string $title, string $detail): array
    {
        return [
            'severity' => $severity,
            'title' => $title,
            'detail' => $detail,
            'user_id' => $user->id,
            'user_name' => $user->name,
        ];
    }
}
