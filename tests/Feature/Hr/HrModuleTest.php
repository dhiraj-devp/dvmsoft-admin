<?php

namespace Tests\Feature\Hr;

use App\Enums\EmployeeDocumentStatus;
use App\Enums\EmployeeDocumentType;
use App\Enums\EmploymentStatus;
use App\Enums\HrChecklistType;
use App\Enums\LeaveRequestStatus;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\EmployeeDocument;
use App\Models\HrChecklistItem;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\EmployeeProvisioningService;
use App\Services\HrChecklistService;
use App\Services\LeaveWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HrModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_moves_pending_to_approved_and_updates_balance(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create();
        $type = LeaveType::factory()->create(['days_per_year' => 10]);
        app(\App\Services\LeaveBalanceService::class)->ensureYear($employee, now()->year);

        $workflow = app(LeaveWorkflowService::class);
        $request = $workflow->request($employee, [
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Travel',
        ], $admin);

        $this->assertSame(LeaveRequestStatus::Pending, $request->status);
        $this->assertSame(2.0, (float) $employee->leaveBalances()->where('leave_type_id', $type->id)->first()->pending);

        $workflow->approve($request, $admin);
        $balance = $employee->leaveBalances()->where('leave_type_id', $type->id)->first();

        $this->assertSame(LeaveRequestStatus::Approved, $request->fresh()->status);
        $this->assertSame(0.0, (float) $balance->pending);
        $this->assertSame(2.0, (float) $balance->used);
        $this->assertSame($admin->id, $request->fresh()->approver_id);
    }

    public function test_rejected_leave_releases_pending_balance(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create();
        $type = LeaveType::factory()->create(['days_per_year' => 8]);
        app(\App\Services\LeaveBalanceService::class)->ensureYear($employee, now()->year);

        $workflow = app(LeaveWorkflowService::class);
        $request = $workflow->request($employee, [
            'leave_type_id' => $type->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason' => 'Personal',
        ], $admin);

        $workflow->reject($request, $admin);
        $balance = $employee->leaveBalances()->where('leave_type_id', $type->id)->first();

        $this->assertSame(LeaveRequestStatus::Rejected, $request->fresh()->status);
        $this->assertSame(0.0, (float) $balance->pending);
        $this->assertSame(0.0, (float) $balance->used);
    }

    public function test_cancelled_leave_releases_pending_balance(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create();
        $type = LeaveType::factory()->create(['days_per_year' => 8]);
        app(\App\Services\LeaveBalanceService::class)->ensureYear($employee, now()->year);

        $workflow = app(LeaveWorkflowService::class);
        $request = $workflow->request($employee, [
            'leave_type_id' => $type->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason' => 'Changed plans',
        ], $admin);

        $workflow->cancel($request);
        $balance = $employee->leaveBalances()->where('leave_type_id', $type->id)->first();

        $this->assertSame(LeaveRequestStatus::Cancelled, $request->fresh()->status);
        $this->assertSame(0.0, (float) $balance->pending);
        $this->assertSame(0.0, (float) $balance->used);
    }

    public function test_employee_documents_use_private_hr_disk_and_require_permission(): void
    {
        Storage::fake('hr');
        Storage::fake('public');

        $admin = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create();
        $path = UploadedFile::fake()->create('nda.pdf', 20, 'application/pdf')->store('employees/'.$employee->id.'/documents', 'hr');

        $document = EmployeeDocument::factory()->create([
            'employee_id' => $employee->id,
            'type' => EmployeeDocumentType::Nda,
            'status' => EmployeeDocumentStatus::Uploaded,
            'path' => $path,
            'disk' => 'hr',
            'original_name' => 'nda.pdf',
        ]);

        Storage::disk('hr')->assertExists($path);
        Storage::disk('public')->assertMissing($path);

        $this->actingAs($admin)
            ->get(route('employees.documents.download', [$employee, $document]))
            ->assertOk();

        $stranger = User::factory()->create();
        $this->actingAs($stranger)
            ->get(route('employees.documents.download', [$employee, $document]))
            ->assertForbidden();
    }

    public function test_onboarding_and_offboarding_checklists_can_be_completed(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create();
        $checklists = app(HrChecklistService::class);

        $onboarding = $checklists->ensure($employee, HrChecklistType::Onboarding);
        $this->assertGreaterThan(0, $onboarding->items->count());
        $this->assertSame('in_progress', $onboarding->status);

        foreach ($onboarding->items as $item) {
            $checklists->toggle($item, $admin, true);
        }

        $this->assertTrue($onboarding->fresh()->isComplete());

        $employee->update([
            'employment_status' => EmploymentStatus::Exited,
            'exit_date' => now()->toDateString(),
            'exit_reason' => 'Resignation',
        ]);

        $offboarding = $checklists->ensure($employee->fresh(), HrChecklistType::Offboarding);
        $this->assertNotEmpty($offboarding->items);
        $this->assertTrue(
            AuditLog::query()->where('module', 'employees')->where('action', 'updated')->exists()
        );
    }

    public function test_creating_an_employee_does_not_store_salary_and_is_audited(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $this->seed(\Database\Seeders\LeaveTypeSeeder::class);

        $employee = app(EmployeeProvisioningService::class)->create([
            'name' => 'Priya Shah',
            'email' => 'priya@dvmsoft.local',
            'phone' => '9999999999',
            'job_title' => 'Designer',
            'department_id' => '',
            'manager_id' => '',
            'date_of_joining' => now()->toDateString(),
            'employee_code' => '',
            'employment_type' => 'full_time',
            'employment_status' => 'probation',
            'probation_days' => 90,
            'probation_status' => 'ongoing',
            'probation_end_date' => now()->addDays(90)->toDateString(),
            'confirmation_date' => '',
            'exit_date' => '',
            'exit_reason' => '',
            'address' => 'Pune',
            'emergency_contact_name' => 'Amit',
            'emergency_contact_phone' => '8888888888',
            'notes' => '',
        ], $admin);

        $this->assertTrue(str_starts_with((string) $employee->code(), 'EMP-'.now()->year.'-'));
        $this->assertArrayNotHasKey('salary', $employee->getAttributes());
        $this->assertArrayNotHasKey('ctc', $employee->getAttributes());
        $this->assertTrue(AuditLog::query()->where('module', 'employees')->where('action', 'created')->exists());
        $this->assertTrue(
            $employee->onboarding?->items->contains(fn (HrChecklistItem $item) => $item->key === 'account_created' && $item->is_completed)
        );

        $this->actingAs($admin)
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertDontSee('Salary')
            ->assertDontSee('Payroll');
    }

    public function test_recording_an_exit_creates_offboarding_and_assets_can_be_returned(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $this->seed(\Database\Seeders\LeaveTypeSeeder::class);
        $employee = app(EmployeeProvisioningService::class)->create([
            'name' => 'Exit Candidate',
            'email' => 'exit@dvmsoft.local',
            'phone' => '',
            'job_title' => 'Engineer',
            'department_id' => '',
            'manager_id' => '',
            'date_of_joining' => now()->subYear()->toDateString(),
            'employee_code' => '',
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'probation_days' => 90,
            'probation_status' => 'confirmed',
            'probation_end_date' => now()->subMonths(9)->toDateString(),
            'confirmation_date' => now()->subMonths(9)->toDateString(),
            'exit_date' => '',
            'exit_reason' => '',
            'address' => '',
            'emergency_contact_name' => '',
            'emergency_contact_phone' => '',
            'notes' => '',
        ], $admin);

        $asset = EmployeeAsset::factory()->create([
            'employee_id' => $employee->id,
            'assigned_by_id' => $admin->id,
            'return_date' => null,
        ]);

        $employee = app(EmployeeProvisioningService::class)->update($employee, [
            'name' => 'Exit Candidate',
            'email' => 'exit@dvmsoft.local',
            'phone' => '',
            'job_title' => 'Engineer',
            'department_id' => '',
            'manager_id' => '',
            'date_of_joining' => now()->subYear()->toDateString(),
            'employment_type' => 'full_time',
            'employment_status' => 'exited',
            'probation_days' => 90,
            'probation_status' => 'confirmed',
            'probation_end_date' => now()->subMonths(9)->toDateString(),
            'confirmation_date' => now()->subMonths(9)->toDateString(),
            'exit_date' => now()->toDateString(),
            'exit_reason' => 'Resignation',
            'address' => '',
            'emergency_contact_name' => '',
            'emergency_contact_phone' => '',
            'notes' => '',
        ]);

        $this->assertNotNull($employee->offboarding);
        $this->assertTrue(
            $employee->offboarding->items->contains(fn (HrChecklistItem $item) => $item->key === 'exit_recorded' && $item->is_completed)
        );

        $asset->update(['return_date' => now()->toDateString()]);
        $this->assertTrue($asset->fresh()->isReturned());
        $this->assertTrue(AuditLog::query()->where('module', 'assets')->exists());
    }
}
