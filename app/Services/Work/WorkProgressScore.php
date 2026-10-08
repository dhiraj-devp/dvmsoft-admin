<?php

namespace App\Services\Work;

use App\Enums\WorkGoalStatus;
use App\Models\User;
use App\Models\WorkDailyPlan;
use App\Models\WorkDailyUpdate;
use App\Models\WorkGoal;
use App\Models\WorkReview;
use App\Support\WorkProgressResult;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class WorkProgressScore
{
    /**
     * @return array<string, int>
     */
    public function weights(): array
    {
        return config('work.progress.weights', [
            'goal_completion' => 35,
            'on_time_completion' => 25,
            'daily_plan_completion' => 15,
            'daily_update_consistency' => 15,
            'manager_review_quality' => 10,
        ]);
    }

    public function forUser(User $user, ?CarbonInterface $asOf = null): WorkProgressResult
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        $lookback = (int) config('work.progress.lookback_days', 14);
        $from = $asOf->copy()->subDays($lookback - 1);
        $minimumUpdates = (int) config('work.progress.minimum_updates', 3);

        $signals = [
            'goal_completion' => [
                'label' => 'Goal completion',
                'weight' => $this->weights()['goal_completion'] ?? 35,
                'value' => $this->goalCompletion($user, $from, $asOf),
            ],
            'on_time_completion' => [
                'label' => 'On-time completion',
                'weight' => $this->weights()['on_time_completion'] ?? 25,
                'value' => $this->onTimeCompletion($user, $from, $asOf),
            ],
            'daily_plan_completion' => [
                'label' => 'Daily plan completion',
                'weight' => $this->weights()['daily_plan_completion'] ?? 15,
                'value' => $this->planCompletion($user, $from, $asOf),
            ],
            'daily_update_consistency' => [
                'label' => 'Daily update consistency',
                'weight' => $this->weights()['daily_update_consistency'] ?? 15,
                'value' => $this->updateConsistency($user, $from, $asOf),
            ],
            'manager_review_quality' => [
                'label' => 'Manager review quality',
                'weight' => $this->weights()['manager_review_quality'] ?? 10,
                'value' => $this->reviewQuality($user, $from, $asOf),
            ],
        ];

        $updateCount = WorkDailyUpdate::query()
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$from->toDateString(), $asOf->toDateString()])
            ->count();

        $available = collect($signals)->filter(fn (array $signal) => $signal['value'] !== null);
        $weightSum = (int) $available->sum('weight');

        if ($updateCount < $minimumUpdates || $available->isEmpty() || $weightSum === 0) {
            return new WorkProgressResult(false, null, $signals);
        }

        $percent = (int) round($available->sum(
            fn (array $signal) => ($signal['value'] * $signal['weight']) / $weightSum
        ));

        return new WorkProgressResult(true, max(0, min(100, $percent)), $signals);
    }

    protected function goalCompletion(User $user, CarbonInterface $from, CarbonInterface $asOf): ?int
    {
        $goals = WorkGoal::query()
            ->where('assigned_user_id', $user->id)
            ->where('status', '!=', WorkGoalStatus::Cancelled->value)
            ->where(function ($query) use ($from, $asOf): void {
                $query->whereBetween('due_date', [$from->toDateString(), $asOf->toDateString()])
                    ->orWhereBetween('start_date', [$from->toDateString(), $asOf->toDateString()])
                    ->orWhere(function ($open) use ($asOf): void {
                        $open->whereIn('status', [
                            WorkGoalStatus::NotStarted->value,
                            WorkGoalStatus::InProgress->value,
                            WorkGoalStatus::Blocked->value,
                        ])->where(function ($dates) use ($asOf): void {
                            $dates->whereNull('due_date')->orWhere('due_date', '>=', $asOf->toDateString());
                        });
                    });
            })
            ->get();

        if ($goals->isEmpty()) {
            $goals = WorkGoal::query()
                ->where('assigned_user_id', $user->id)
                ->where('status', '!=', WorkGoalStatus::Cancelled->value)
                ->get();
        }

        if ($goals->isEmpty()) {
            return null;
        }

        $completed = $goals->where('status', WorkGoalStatus::Completed)->count();

        return (int) round(($completed / $goals->count()) * 100);
    }

    protected function onTimeCompletion(User $user, CarbonInterface $from, CarbonInterface $asOf): ?int
    {
        $completed = WorkGoal::query()
            ->where('assigned_user_id', $user->id)
            ->where('status', WorkGoalStatus::Completed->value)
            ->whereBetween('updated_at', [$from->copy()->startOfDay(), $asOf->copy()->endOfDay()])
            ->get();

        if ($completed->isEmpty()) {
            return null;
        }

        $onTime = $completed->filter(function (WorkGoal $goal): bool {
            return $goal->due_date === null || $goal->updated_at->lte($goal->due_date->endOfDay());
        })->count();

        return (int) round(($onTime / $completed->count()) * 100);
    }

    protected function planCompletion(User $user, CarbonInterface $from, CarbonInterface $asOf): ?int
    {
        $plans = WorkDailyPlan::query()
            ->with('items')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$from->toDateString(), $asOf->toDateString()])
            ->get();

        $ratios = $plans->map->completionRatio()->filter(fn ($ratio) => $ratio !== null);

        if ($ratios->isEmpty()) {
            return null;
        }

        return (int) round($ratios->avg() * 100);
    }

    protected function updateConsistency(User $user, CarbonInterface $from, CarbonInterface $asOf): ?int
    {
        $expected = 0;
        $cursor = $from->copy();

        while ($cursor->lte($asOf)) {
            if ($cursor->isWeekday()) {
                $expected++;
            }
            $cursor->addDay();
        }

        if ($expected === 0) {
            return null;
        }

        $submitted = WorkDailyUpdate::query()
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$from->toDateString(), $asOf->toDateString()])
            ->whereNotNull('submitted_at')
            ->count();

        return (int) round(min($submitted, $expected) / $expected * 100);
    }

    protected function reviewQuality(User $user, CarbonInterface $from, CarbonInterface $asOf): ?int
    {
        $reviews = WorkReview::query()
            ->where('user_id', $user->id)
            ->whereBetween('reviewed_on', [$from->toDateString(), $asOf->toDateString()])
            ->get();

        if ($reviews->isEmpty()) {
            return null;
        }

        return (int) round(($reviews->avg('quality_score') / 5) * 100);
    }
}
