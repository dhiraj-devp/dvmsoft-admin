<?php

namespace App\Livewire\HrChecklists;

use App\Enums\HrChecklistType;
use App\Models\Employee;
use App\Models\HrChecklistItem;
use App\Services\HrChecklistService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public string $type = 'onboarding';

    public ?string $employeeId = null;

    public function mount(string $type = 'onboarding', ?string $employeeId = null): void
    {
        $this->type = $type;
        $this->employeeId = $employeeId;
        $this->authorize($this->permission('view'));
    }

    public function toggle(string $id, HrChecklistService $checklists): void
    {
        $this->authorize($this->permission('manage'));
        $item = HrChecklistItem::query()->findOrFail($id);
        $checklists->toggle($item, request()->user(), ! $item->is_completed);
        $this->dispatch('notify', type: 'success', message: 'Checklist updated.');
    }

    public function render(HrChecklistService $checklists): View
    {
        $enum = HrChecklistType::from($this->type);

        if ($this->employeeId) {
            $employee = Employee::query()->with('user')->findOrFail($this->employeeId);
            $checklist = $checklists->ensure($employee, $enum);

            return view('livewire.hr-checklists.index', [
                'rows' => collect([['employee' => $employee, 'checklist' => $checklist->fresh('items')]]),
                'single' => true,
            ]);
        }

        $query = Employee::query()->with(['user', 'checklists.items']);

        if ($enum === HrChecklistType::Offboarding) {
            $query->where(function ($nested) {
                $nested->whereNotNull('exit_date')
                    ->orWhere('employment_status', 'exited')
                    ->orWhereHas('checklists', fn ($checklistsQuery) => $checklistsQuery->where('type', HrChecklistType::Offboarding->value));
            });
        } else {
            $query->active();
        }

        $employees = $query->orderByDesc('updated_at')->limit(50)->get();

        $rows = $employees->map(function (Employee $employee) use ($checklists, $enum) {
            return [
                'employee' => $employee,
                'checklist' => $checklists->ensure($employee, $enum),
            ];
        });

        return view('livewire.hr-checklists.index', [
            'rows' => $rows,
            'single' => false,
        ]);
    }

    protected function permission(string $action): string
    {
        return $this->type.'.'.$action;
    }
}
