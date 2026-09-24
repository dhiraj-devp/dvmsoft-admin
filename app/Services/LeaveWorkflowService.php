<?php

namespace App\Services;

use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveWorkflowService
{
    public function __construct(protected LeaveBalanceService $balances) {}

    public function days(string $start, string $end): float
    {
        $from = Carbon::parse($start)->startOfDay();
        $to = Carbon::parse($end)->startOfDay();

        if ($to->lt($from)) {
            throw ValidationException::withMessages([
                'end_date' => 'The end date must be on or after the start date.',
            ]);
        }

        return (float) ($from->diffInDays($to) + 1);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function request(Employee $employee, array $attributes, User $actor): LeaveRequest
    {
        $days = $this->days($attributes['start_date'], $attributes['end_date']);
        $type = LeaveType::query()->findOrFail($attributes['leave_type_id']);
        $year = Carbon::parse($attributes['start_date'])->year;
        $balance = $this->balances->for($employee, $type, $year);

        if ($type->days_per_year > 0 && $days - $balance->available() > 0.009) {
            throw ValidationException::withMessages([
                'days' => 'Only '.$balance->available().' day(s) remain for '.$type->name.'.',
            ]);
        }

        return DB::transaction(function () use ($employee, $attributes, $actor, $days, $type, $balance) {
            $request = LeaveRequest::query()->create([
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'requested_by_id' => $actor->id,
                'start_date' => $attributes['start_date'],
                'end_date' => $attributes['end_date'],
                'days' => $days,
                'reason' => $attributes['reason'] ?: null,
                'status' => LeaveRequestStatus::Pending,
            ]);

            if ($type->days_per_year > 0) {
                $balance->increment('pending', $days);
            }

            return $request->fresh(['employee.user', 'leaveType']);
        });
    }

    public function approve(LeaveRequest $request, User $actor, ?string $notes = null): LeaveRequest
    {
        $this->assertPending($request);

        return DB::transaction(function () use ($request, $actor, $notes) {
            $balance = $this->balances->for(
                $request->employee,
                $request->leaveType,
                $request->start_date->year,
            );

            if ((float) $request->leaveType->days_per_year > 0) {
                $balance->decrement('pending', (float) $request->days);
                $balance->increment('used', (float) $request->days);
            }

            $request->update([
                'status' => LeaveRequestStatus::Approved,
                'approver_id' => $actor->id,
                'decided_at' => now(),
                'decision_notes' => $notes,
            ]);

            return $request->fresh();
        });
    }

    public function reject(LeaveRequest $request, User $actor, ?string $notes = null): LeaveRequest
    {
        $this->assertPending($request);

        return DB::transaction(function () use ($request, $actor, $notes) {
            $this->releasePending($request);

            $request->update([
                'status' => LeaveRequestStatus::Rejected,
                'approver_id' => $actor->id,
                'decided_at' => now(),
                'decision_notes' => $notes,
            ]);

            return $request->fresh();
        });
    }

    public function cancel(LeaveRequest $request): LeaveRequest
    {
        if (! in_array($request->status, [LeaveRequestStatus::Pending, LeaveRequestStatus::Approved], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only pending or approved leave can be cancelled.',
            ]);
        }

        return DB::transaction(function () use ($request) {
            $balance = $this->balances->for(
                $request->employee,
                $request->leaveType,
                $request->start_date->year,
            );

            if ((float) $request->leaveType->days_per_year > 0) {
                if ($request->status === LeaveRequestStatus::Pending) {
                    $balance->decrement('pending', (float) $request->days);
                } else {
                    $balance->decrement('used', (float) $request->days);
                }
            }

            $request->update([
                'status' => LeaveRequestStatus::Cancelled,
                'decided_at' => now(),
            ]);

            return $request->fresh();
        });
    }

    protected function assertPending(LeaveRequest $request): void
    {
        if ($request->status !== LeaveRequestStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending leave requests can be decided.',
            ]);
        }
    }

    protected function releasePending(LeaveRequest $request): void
    {
        if ((float) $request->leaveType->days_per_year <= 0) {
            return;
        }

        $balance = $this->balances->for(
            $request->employee,
            $request->leaveType,
            $request->start_date->year,
        );

        $balance->decrement('pending', (float) $request->days);
    }
}
