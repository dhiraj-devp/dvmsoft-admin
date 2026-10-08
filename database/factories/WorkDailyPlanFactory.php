<?php

namespace Database\Factories;

use App\Enums\WorkPlanItemStatus;
use App\Models\User;
use App\Models\WorkDailyPlan;
use App\Models\WorkDailyPlanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkDailyPlan>
 */
class WorkDailyPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'work_date' => now()->toDateString(),
            'focus' => fake()->sentence(),
        ];
    }

    public function withItems(int $count = 2, WorkPlanItemStatus $status = WorkPlanItemStatus::Planned): static
    {
        return $this->afterCreating(function (WorkDailyPlan $plan) use ($count, $status): void {
            for ($i = 0; $i < $count; $i++) {
                WorkDailyPlanItem::query()->create([
                    'work_daily_plan_id' => $plan->id,
                    'sort_order' => $i,
                    'title' => fake()->sentence(3),
                    'status' => $status,
                ]);
            }
        });
    }
}
