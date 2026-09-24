<?php

namespace App\Services;

use App\Enums\HrChecklistType;
use App\Models\Employee;
use App\Models\HrChecklist;
use App\Models\HrChecklistItem;
use App\Models\User;

class HrChecklistService
{
    public function ensure(Employee $employee, HrChecklistType $type): HrChecklist
    {
        $checklist = HrChecklist::query()->firstOrCreate(
            [
                'employee_id' => $employee->id,
                'type' => $type->value,
            ],
            [
                'status' => 'in_progress',
            ]
        );

        foreach ($type->items() as $index => $item) {
            HrChecklistItem::query()->firstOrCreate(
                [
                    'hr_checklist_id' => $checklist->id,
                    'key' => $item['key'],
                ],
                [
                    'label' => $item['label'],
                    'sort_order' => $index,
                ]
            );
        }

        return $checklist->fresh('items');
    }

    public function toggle(HrChecklistItem $item, User $actor, bool $completed): HrChecklistItem
    {
        $item->update([
            'is_completed' => $completed,
            'completed_at' => $completed ? now() : null,
            'completed_by_id' => $completed ? $actor->id : null,
        ]);

        $this->refresh($item->checklist);

        return $item->fresh();
    }

    public function completeItem(Employee $employee, HrChecklistType $type, string $key, User $actor): void
    {
        $checklist = $this->ensure($employee, $type);
        $item = $checklist->items->firstWhere('key', $key);

        if ($item && ! $item->is_completed) {
            $this->toggle($item, $actor, true);
        }
    }

    public function refresh(HrChecklist $checklist): void
    {
        $checklist->load('items');
        $complete = $checklist->items->isNotEmpty() && $checklist->items->every(fn (HrChecklistItem $item) => $item->is_completed);

        $checklist->forceFill([
            'status' => $complete ? 'completed' : 'in_progress',
            'completed_at' => $complete ? ($checklist->completed_at ?? now()) : null,
        ])->save();
    }
}
