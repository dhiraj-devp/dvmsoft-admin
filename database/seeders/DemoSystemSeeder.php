<?php

namespace Database\Seeders;

use App\Enums\AssetCondition;
use App\Enums\ChangeRequestStatus;
use App\Enums\ClientApprovalMode;
use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Enums\DocumentStatus;
use App\Enums\ExpenseStatus;
use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Enums\InvoiceStatus;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\MilestoneStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\QuotationStatus;
use App\Enums\RequirementApprovalStatus;
use App\Enums\RequirementStatus;
use App\Enums\StageApprovalRequirement;
use App\Enums\StageStatus;
use App\Enums\TaskStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\WorkDailyUpdateStatus;
use App\Enums\WorkGoalStatus;
use App\Enums\WorkLearningStatus;
use App\Enums\WorkManagerStamp;
use App\Enums\WorkPriority;
use App\Models\ChangeRequest;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Contact;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\EmployeeAsset;
use App\Models\Expense;
use App\Models\FollowUp;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Milestone;
use App\Models\OfficeHoliday;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectStage;
use App\Models\Quotation;
use App\Models\Requirement;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\WorkDailyUpdate;
use App\Models\WorkGoal;
use App\Models\WorkLearningGoal;
use App\Services\LeaveBalanceService;
use App\Services\QuotationCalculator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DemoSystemSeeder extends Seeder
{
    use WithoutModelEvents;

    public const PORTAL_PASSWORD = 'password';

    public function run(QuotationCalculator $calculator, LeaveBalanceService $leaveBalances): void
    {
        $staff = $this->staff();

        $this->seedHolidays($staff['admin']);
        $this->seedHr($staff, $leaveBalances);

        $leads = $this->seedLeads($staff);
        $clients = $this->seedClients($staff, $leads);
        $quotations = $this->seedQuotations($staff, $clients, $leads, $calculator);
        $projects = $this->seedProjects($staff, $clients, $quotations);
        $this->seedFinance($staff, $clients, $projects, $quotations, $calculator);
        $this->seedSupport($staff, $clients, $projects);
        $this->seedDocuments($staff, $clients, $projects, $quotations);
        $this->seedFollowUps($staff, $leads, $clients);
        $this->seedWork($staff, $projects);
    }

    /**
     * @return array<string, User>
     */
    protected function staff(): array
    {
        $emails = [
            'admin' => SuperAdminSeeder::ADMIN_EMAIL,
            'hr' => 'kavya.singh@dvmsoft.local',
            'sales' => 'priya.sharma@dvmsoft.local',
            'executive' => 'arjun.mehta@dvmsoft.local',
            'executive_west' => 'neha.patel@dvmsoft.local',
            'pm' => 'vikram.iyer@dvmsoft.local',
            'dev' => 'karthik.reddy@dvmsoft.local',
            'dev_east' => 'ananya.das@dvmsoft.local',
            'intern' => 'divya.krishnan@dvmsoft.local',
            'design' => 'sneha.deshpande@dvmsoft.local',
            'qa' => 'aditi.rao@dvmsoft.local',
            'support' => 'rohan.verma@dvmsoft.local',
            'finance' => 'anjali.nair@dvmsoft.local',
        ];

        return collect($emails)
            ->mapWithKeys(fn (string $email, string $key) => [$key => User::query()->where('email', $email)->firstOrFail()])
            ->all();
    }

    protected function seedHolidays(User $admin): void
    {
        foreach ([
            '2026-01-26' => 'Republic Day',
            '2026-03-03' => 'Holi',
            '2026-05-01' => 'Maharashtra Day',
            '2026-08-15' => 'Independence Day',
            '2026-10-02' => 'Gandhi Jayanti',
            '2026-11-08' => 'Diwali',
            '2026-12-25' => 'Christmas',
        ] as $date => $title) {
            OfficeHoliday::query()->firstOrCreate(
                ['holiday_date' => $date],
                ['title' => $title, 'created_by' => $admin->id],
            );
        }
    }

    /**
     * @param  array<string, User>  $staff
     */
    protected function seedHr(array $staff, LeaveBalanceService $leaveBalances): void
    {
        $annual = LeaveType::query()->where('code', 'annual')->first();
        $casual = LeaveType::query()->where('code', 'casual')->first();
        $year = 2026;

        foreach ($staff as $user) {
            if ($user->employee) {
                $leaveBalances->ensureYear($user->employee, $year);
            }
        }

        if ($annual && $staff['dev']->employee) {
            $this->leave(
                $staff['dev']->employee->id,
                $annual->id,
                $staff['dev']->id,
                $staff['hr']->id,
                '2026-09-14',
                '2026-09-16',
                3,
                'Ganesh Chaturthi at home in Hyderabad',
                LeaveRequestStatus::Approved,
            );
        }

        if ($casual && $staff['executive']->employee) {
            $this->leave(
                $staff['executive']->employee->id,
                $casual->id,
                $staff['executive']->id,
                null,
                '2026-11-09',
                '2026-11-09',
                1,
                'Family function in Mumbai after Diwali',
                LeaveRequestStatus::Pending,
            );
        }

        foreach ([
            [$staff['pm'], 'MacBook Pro 14', 'C02-DVM-7781', '2024-01-12'],
            [$staff['dev'], 'MacBook Air M3', 'C02-DVM-8820', '2023-09-06'],
            [$staff['design'], 'iPad Pro 12.9', 'IPD-DVM-4412', '2022-03-01'],
        ] as [$user, $name, $serial, $assigned]) {
            if (! $user->employee) {
                continue;
            }

            EmployeeAsset::query()->firstOrCreate(
                ['serial_number' => $serial],
                [
                    'employee_id' => $user->employee->id,
                    'assigned_by_id' => $staff['hr']->id,
                    'name' => $name,
                    'assigned_date' => $assigned,
                    'condition' => AssetCondition::Good,
                    'notes' => 'Company laptop issued from Bengaluru office.',
                ],
            );
        }
    }

    /**
     * @param  array<string, User>  $staff
     * @return array<string, Lead>
     */
    protected function seedLeads(array $staff): array
    {
        $records = [
            'nimbus' => [
                'name' => 'Ravi Kulkarni',
                'company' => 'Nimbus Retail Pvt Ltd',
                'email' => 'ravi.kulkarni@nimbusretail.in',
                'phone' => '+91 98210 33441',
                'source' => 'referral',
                'requirement' => 'Custom e-commerce and warehouse dashboard for 40 stores across Maharashtra.',
                'estimated_value' => 1850000,
                'status' => LeadStatus::Won,
                'priority' => LeadPriority::High,
                'assigned' => 'sales',
                'converted_at' => '2025-11-18 11:00:00',
            ],
            'sahyadri' => [
                'name' => 'Dr Meera Joshi',
                'company' => 'Sahyadri Care Pvt Ltd',
                'email' => 'meera.joshi@sahyadricare.in',
                'phone' => '+91 98220 11880',
                'source' => 'event',
                'requirement' => 'Patient portal with appointment booking and lab reports for Pune hospitals.',
                'estimated_value' => 2400000,
                'status' => LeadStatus::Won,
                'priority' => LeadPriority::Urgent,
                'assigned' => 'sales',
                'converted_at' => '2026-02-04 10:30:00',
            ],
            'kaveri' => [
                'name' => 'Suresh Hegde',
                'company' => 'Kaveri Agro Foods',
                'email' => 'suresh.hegde@kaveriagro.in',
                'phone' => '+91 98440 22771',
                'source' => 'website',
                'requirement' => 'Inventory and GST invoicing for spice processing units in Bengaluru.',
                'estimated_value' => 780000,
                'status' => LeadStatus::Won,
                'priority' => LeadPriority::Medium,
                'assigned' => 'executive',
                'converted_at' => '2026-07-21 16:00:00',
            ],
            'meenakshi' => [
                'name' => 'Lakshmi Narayanan',
                'company' => 'Meenakshi Logistics',
                'email' => 'lakshmi.n@meenakshilog.in',
                'phone' => '+91 98400 55661',
                'source' => 'linkedin',
                'requirement' => 'Fleet tracking and POD app for Chennai to Coimbatore routes.',
                'estimated_value' => 920000,
                'status' => LeadStatus::Contacted,
                'priority' => LeadPriority::Medium,
                'assigned' => 'executive',
                'next_follow_up_at' => now()->addDays(2)->setTime(11, 0),
            ],
            'jaipur' => [
                'name' => 'Aman Singh Rathore',
                'company' => 'Amber EduTech',
                'email' => 'aman.rathore@amberedutech.in',
                'phone' => '+91 98290 33440',
                'source' => 'website',
                'requirement' => 'Learning management system for 12 coaching centres in Jaipur.',
                'estimated_value' => 640000,
                'status' => LeadStatus::Qualified,
                'priority' => LeadPriority::High,
                'assigned' => 'executive_west',
                'next_follow_up_at' => now()->addDay()->setTime(16, 30),
            ],
            'delhi' => [
                'name' => 'Ishaan Kapoor',
                'company' => 'Yamuna Nivesh',
                'email' => 'ishaan.kapoor@yamunanivesh.in',
                'phone' => '+91 98100 77882',
                'source' => 'partner',
                'requirement' => 'Investor onboarding portal with CKYC and payout reports.',
                'estimated_value' => 3100000,
                'status' => LeadStatus::Proposal,
                'priority' => LeadPriority::Urgent,
                'assigned' => 'sales',
                'next_follow_up_at' => now()->addDays(3)->setTime(15, 0),
            ],
            'lost' => [
                'name' => 'Farhan Qureshi',
                'company' => 'Deccan Print House',
                'email' => 'farhan@deccanprint.in',
                'phone' => '+91 98850 22119',
                'source' => 'cold_call',
                'requirement' => 'Print job tracker for Hyderabad plant.',
                'estimated_value' => 280000,
                'status' => LeadStatus::Lost,
                'priority' => LeadPriority::Low,
                'assigned' => 'executive',
                'lost_reason' => 'Went with an in-house Excel workflow after budget freeze.',
            ],
        ];

        $leads = [];

        foreach ($records as $key => $row) {
            $leads[$key] = Lead::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'assigned_user_id' => $staff[$row['assigned']]->id,
                    'name' => $row['name'],
                    'company' => $row['company'],
                    'phone' => $row['phone'],
                    'source' => $row['source'],
                    'requirement' => $row['requirement'],
                    'estimated_value' => $row['estimated_value'],
                    'status' => $row['status'],
                    'priority' => $row['priority'],
                    'next_follow_up_at' => $row['next_follow_up_at'] ?? null,
                    'converted_at' => $row['converted_at'] ?? null,
                    'lost_reason' => $row['lost_reason'] ?? null,
                    'notes' => 'Demo Indian pipeline record.',
                ],
            );
        }

        return $leads;
    }

    /**
     * @param  array<string, User>  $staff
     * @param  array<string, Lead>  $leads
     * @return array<string, Client>
     */
    protected function seedClients(array $staff, array $leads): array
    {
        $records = [
            'nimbus' => [
                'name' => 'Nimbus Retail Pvt Ltd',
                'email' => 'accounts@nimbusretail.in',
                'phone' => '+91 22 4921 8800',
                'website' => 'https://nimbusretail.in',
                'address' => '6th Floor, Windsor, Off CST Road, Kalina, Mumbai 400098',
                'gst_number' => '27AABCN1234A1Z5',
                'pan_number' => 'AABCN1234A',
                'manager' => 'sales',
                'lead' => 'nimbus',
                'contacts' => [
                    ['name' => 'Ravi Kulkarni', 'email' => 'ravi.kulkarni@nimbusretail.in', 'phone' => '+91 98210 33441', 'job_title' => 'Head of Technology', 'is_primary' => true],
                    ['name' => 'Smita Rao', 'email' => 'smita.rao@nimbusretail.in', 'phone' => '+91 98210 33442', 'job_title' => 'Finance Controller', 'is_primary' => false],
                ],
                'portal' => ['name' => 'Ravi Kulkarni', 'email' => 'ravi.kulkarni@nimbusretail.in'],
            ],
            'sahyadri' => [
                'name' => 'Sahyadri Care Pvt Ltd',
                'email' => 'it@sahyadricare.in',
                'phone' => '+91 20 6721 5500',
                'website' => 'https://sahyadricare.in',
                'address' => 'Sahyadri Speciality Hospital, Karve Road, Pune 411004',
                'gst_number' => '27AADCS5678B1Z1',
                'pan_number' => 'AADCS5678B',
                'manager' => 'sales',
                'lead' => 'sahyadri',
                'contacts' => [
                    ['name' => 'Dr Meera Joshi', 'email' => 'meera.joshi@sahyadricare.in', 'phone' => '+91 98220 11880', 'job_title' => 'Medical Director', 'is_primary' => true],
                    ['name' => 'Nikhil Kulkarni', 'email' => 'nikhil.kulkarni@sahyadricare.in', 'phone' => '+91 98220 11881', 'job_title' => 'IT Manager', 'is_primary' => false],
                ],
                'portal' => ['name' => 'Nikhil Kulkarni', 'email' => 'nikhil.kulkarni@sahyadricare.in'],
            ],
            'kaveri' => [
                'name' => 'Kaveri Agro Foods',
                'email' => 'ops@kaveriagro.in',
                'phone' => '+91 80 4122 7700',
                'website' => 'https://kaveriagro.in',
                'address' => 'Plot 42, Jigani Industrial Area, Bengaluru 560105',
                'gst_number' => '29AABCK9012C1Z8',
                'pan_number' => 'AABCK9012C',
                'manager' => 'executive',
                'lead' => 'kaveri',
                'contacts' => [
                    ['name' => 'Suresh Hegde', 'email' => 'suresh.hegde@kaveriagro.in', 'phone' => '+91 98440 22771', 'job_title' => 'Managing Partner', 'is_primary' => true],
                ],
                'portal' => ['name' => 'Suresh Hegde', 'email' => 'suresh.hegde@kaveriagro.in'],
            ],
            'yamuna' => [
                'name' => 'Yamuna Nivesh',
                'email' => 'hello@yamunanivesh.in',
                'phone' => '+91 11 4155 2200',
                'website' => 'https://yamunanivesh.in',
                'address' => '3rd Floor, Tolstoy House, Connaught Place, New Delhi 110001',
                'gst_number' => '07AABCY3344D1Z2',
                'pan_number' => 'AABCY3344D',
                'manager' => 'sales',
                'lead' => null,
                'status' => ClientStatus::Prospect,
                'contacts' => [
                    ['name' => 'Ishaan Kapoor', 'email' => 'ishaan.kapoor@yamunanivesh.in', 'phone' => '+91 98100 77882', 'job_title' => 'Founder', 'is_primary' => true],
                ],
                'portal' => null,
            ],
        ];

        $clients = [];

        foreach ($records as $key => $row) {
            $lead = $row['lead'] ? $leads[$row['lead']] : null;
            $client = Client::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'account_manager_id' => $staff[$row['manager']]->id,
                    'converted_from_lead_id' => $lead?->id,
                    'type' => ClientType::Company,
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'website' => $row['website'],
                    'address' => $row['address'],
                    'gst_number' => $row['gst_number'],
                    'pan_number' => $row['pan_number'],
                    'status' => $row['status'] ?? ClientStatus::Active,
                    'notes' => 'Indian client seeded for demos.',
                ],
            );

            if ($lead) {
                $lead->update([
                    'converted_client_id' => $client->id,
                    'status' => LeadStatus::Won,
                    'converted_at' => $lead->converted_at ?? now(),
                ]);
            }

            foreach ($row['contacts'] as $contact) {
                Contact::query()->updateOrCreate(
                    ['email' => $contact['email']],
                    [
                        'client_id' => $client->id,
                        'name' => $contact['name'],
                        'phone' => $contact['phone'],
                        'job_title' => $contact['job_title'],
                        'is_primary' => $contact['is_primary'],
                    ],
                );
            }

            if ($row['portal']) {
                ClientUser::query()->firstOrCreate(
                    ['email' => $row['portal']['email']],
                    [
                        'client_id' => $client->id,
                        'name' => $row['portal']['name'],
                        'password' => self::PORTAL_PASSWORD,
                        'is_active' => true,
                        'theme' => 'system',
                        'email_verified_at' => now(),
                        'remember_token' => Str::random(10),
                    ],
                );
            }

            $clients[$key] = $client;
        }

        return $clients;
    }

    /**
     * @param  array<string, User>  $staff
     * @param  array<string, Client>  $clients
     * @param  array<string, Lead>  $leads
     * @return array<string, Quotation>
     */
    protected function seedQuotations(array $staff, array $clients, array $leads, QuotationCalculator $calculator): array
    {
        $definitions = [
            'nimbus' => [
                'number' => 'QT-2025-0001',
                'title' => 'Nimbus retail platform — discovery to go-live',
                'client' => 'nimbus',
                'lead' => 'nimbus',
                'created_by' => 'sales',
                'status' => QuotationStatus::Converted,
                'discount' => 5,
                'terms' => 'milestone',
                'valid_until' => '2025-12-15',
                'sent_at' => '2025-11-10 09:30:00',
                'accepted_at' => '2025-11-18 11:00:00',
                'converted_at' => '2025-11-18 11:20:00',
                'items' => [
                    ['Discovery and process mapping', 1, 180000],
                    ['Storefront and admin build', 1, 980000],
                    ['Warehouse dashboard', 1, 420000],
                    ['GST-ready invoicing module', 1, 160000],
                ],
            ],
            'sahyadri' => [
                'number' => 'QT-2026-0002',
                'title' => 'Sahyadri patient portal',
                'client' => 'sahyadri',
                'lead' => 'sahyadri',
                'created_by' => 'sales',
                'status' => QuotationStatus::Converted,
                'discount' => 0,
                'terms' => 'net_30',
                'valid_until' => '2026-03-01',
                'sent_at' => '2026-01-28 14:00:00',
                'accepted_at' => '2026-02-04 10:30:00',
                'converted_at' => '2026-02-04 10:45:00',
                'items' => [
                    ['Patient portal and appointments', 1, 1250000],
                    ['Lab report viewer and HIS integration', 1, 680000],
                    ['Security audit and hosting setup', 1, 220000],
                ],
            ],
            'kaveri' => [
                'number' => 'QT-2026-0003',
                'title' => 'Kaveri inventory and GST billing',
                'client' => 'kaveri',
                'lead' => 'kaveri',
                'created_by' => 'executive',
                'status' => QuotationStatus::Sent,
                'discount' => 0,
                'terms' => 'net_15',
                'valid_until' => now()->addDays(12)->toDateString(),
                'sent_at' => now()->subDays(3)->toDateTimeString(),
                'items' => [
                    ['Inventory and batch tracking', 1, 420000],
                    ['GST e-invoice connector', 1, 180000],
                ],
            ],
            'delhi' => [
                'number' => 'QT-2026-0004',
                'title' => 'Yamuna Nivesh onboarding portal — draft',
                'client' => 'yamuna',
                'lead' => 'delhi',
                'created_by' => 'sales',
                'status' => QuotationStatus::Draft,
                'discount' => 0,
                'terms' => 'net_30',
                'valid_until' => now()->addDays(21)->toDateString(),
                'items' => [
                    ['CKYC onboarding and payouts', 1, 2100000],
                    ['Investor reports and MIS', 1, 540000],
                ],
            ],
        ];

        $quotations = [];

        foreach ($definitions as $key => $row) {
            $totals = $calculator->calculate(
                collect($row['items'])->map(fn (array $item) => [
                    'description' => $item[0],
                    'quantity' => 1,
                    'unit_price' => $item[2],
                    'discount_percent' => 0,
                    'tax_percent' => 18,
                ])->all(),
                $row['discount'],
            );

            $quotation = Quotation::query()->updateOrCreate(
                ['number' => $row['number']],
                [
                    'client_id' => $row['client'] ? $clients[$row['client']]->id : null,
                    'lead_id' => $leads[$row['lead']]->id,
                    'created_by_id' => $staff[$row['created_by']]->id,
                    'title' => $row['title'],
                    'status' => $row['status'],
                    'subtotal' => $totals['subtotal'],
                    'discount_percent' => $row['discount'],
                    'discount_amount' => $totals['discount_amount'],
                    'tax_percent' => 18,
                    'tax_amount' => $totals['tax_amount'],
                    'total' => $totals['total'],
                    'payment_terms' => $row['terms'],
                    'valid_until' => $row['valid_until'],
                    'sent_at' => $row['sent_at'] ?? null,
                    'accepted_at' => $row['accepted_at'] ?? null,
                    'converted_at' => $row['converted_at'] ?? null,
                    'notes' => 'Prices in INR, GST extra at 18%.',
                ],
            );

            if ($quotation->items()->count() === 0) {
                foreach ($totals['items'] as $item) {
                    $quotation->items()->create($item);
                }
            }

            $quotations[$key] = $quotation->fresh('items');
        }

        return $quotations;
    }

    /**
     * @param  array<string, User>  $staff
     * @param  array<string, Client>  $clients
     * @param  array<string, Quotation>  $quotations
     * @return array<string, Project>
     */
    protected function seedProjects(array $staff, array $clients, array $quotations): array
    {
        $nimbus = Project::query()->updateOrCreate(
            ['number' => 'PRJ-2025-0001'],
            [
                'client_id' => $clients['nimbus']->id,
                'quotation_id' => $quotations['nimbus']->id,
                'manager_id' => $staff['pm']->id,
                'name' => 'Nimbus storefront and warehouse',
                'description' => 'Multi-store e-commerce, inventory sync, and GST billing for Nimbus Retail.',
                'start_date' => '2025-12-01',
                'expected_end_date' => '2026-06-30',
                'budget' => $quotations['nimbus']->total,
                'status' => ProjectStatus::Active,
                'health' => ProjectHealth::Green,
                'priority' => ProjectPriority::High,
                'client_approval_mode' => ClientApprovalMode::Flexible,
            ],
        );

        $sahyadri = Project::query()->updateOrCreate(
            ['number' => 'PRJ-2026-0002'],
            [
                'client_id' => $clients['sahyadri']->id,
                'quotation_id' => $quotations['sahyadri']->id,
                'manager_id' => $staff['pm']->id,
                'name' => 'Sahyadri patient portal',
                'description' => 'Appointments, lab reports, and patient login for Sahyadri Care hospitals.',
                'start_date' => '2026-02-10',
                'expected_end_date' => '2026-09-30',
                'budget' => $quotations['sahyadri']->total,
                'status' => ProjectStatus::Active,
                'health' => ProjectHealth::Yellow,
                'priority' => ProjectPriority::Urgent,
                'client_approval_mode' => ClientApprovalMode::Strict,
            ],
        );

        $this->members($nimbus, [
            [$staff['pm'], 'project_manager'],
            [$staff['dev'], 'tech_lead'],
            [$staff['dev_east'], 'developer'],
            [$staff['design'], 'designer'],
            [$staff['qa'], 'qa'],
        ]);

        $this->members($sahyadri, [
            [$staff['pm'], 'project_manager'],
            [$staff['dev'], 'developer'],
            [$staff['intern'], 'developer'],
            [$staff['qa'], 'qa'],
        ]);

        $this->seedNimbusDelivery($nimbus, $staff);
        $this->seedSahyadriDelivery($sahyadri, $staff);

        return ['nimbus' => $nimbus, 'sahyadri' => $sahyadri];
    }

    /**
     * @param  list<array{0: User, 1: string}>  $members
     */
    protected function members(Project $project, array $members): void
    {
        foreach ($members as [$user, $role]) {
            ProjectMember::query()->firstOrCreate(
                ['project_id' => $project->id, 'user_id' => $user->id],
                ['role' => $role],
            );
        }
    }

    /**
     * @param  array<string, User>  $staff
     */
    protected function seedNimbusDelivery(Project $project, array $staff): void
    {
        $discovery = $this->stage($project, 1, 'Discovery', StageStatus::Completed, '2025-12-01', '2026-01-15', 100, $staff['pm'], '2026-01-14 18:00:00');
        $build = $this->stage($project, 2, 'Build', StageStatus::InProgress, '2026-01-16', '2026-05-15', 62, $staff['dev']);
        $this->stage($project, 3, 'QA and go-live', StageStatus::NotStarted, '2026-05-16', '2026-06-30', 0, $staff['qa']);

        $this->milestone($project, 'Process sign-off', '2026-01-15', MilestoneStatus::Completed, 100, '2026-01-14 18:00:00');
        $this->milestone($project, 'Storefront beta', '2026-04-30', MilestoneStatus::InProgress, 60);
        $this->milestone($project, 'Warehouse go-live', '2026-06-30', MilestoneStatus::Pending, 0);

        $this->requirement($project, 'Multi-store catalogue with GST slabs', RequirementStatus::Done, RequirementApprovalStatus::Approved, ProjectPriority::High);
        $this->requirement($project, 'Warehouse pick-list and stock transfer', RequirementStatus::InProgress, RequirementApprovalStatus::Approved, ProjectPriority::High);

        $this->task($project, $discovery, $staff['pm'], 'Workshop with Kalina store managers', TaskStatus::Completed, '2025-12-08');
        $this->task($project, $build, $staff['dev'], 'Checkout and Razorpay UPI flow', TaskStatus::InProgress, '2026-04-20');
        $this->task($project, $build, $staff['dev_east'], 'GST invoice PDF in English and Marathi', TaskStatus::Review, '2026-04-12');
        $this->task($project, $build, $staff['design'], 'Storefront theme for festive sale', TaskStatus::Completed, '2026-03-02');
        $this->task($project, $build, $staff['qa'], 'Regression on Android Chrome', TaskStatus::Todo, '2026-05-10');
    }

    /**
     * @param  array<string, User>  $staff
     */
    protected function seedSahyadriDelivery(Project $project, array $staff): void
    {
        $this->stage($project, 1, 'HIS integration', StageStatus::Completed, '2026-02-10', '2026-04-15', 100, $staff['dev'], '2026-04-12 17:00:00');
        $portal = $this->stage($project, 2, 'Patient portal', StageStatus::ReadyForReview, '2026-04-16', '2026-07-31', 80, $staff['dev']);
        $this->stage($project, 3, 'Hospital rollout', StageStatus::NotStarted, '2026-08-01', '2026-09-30', 0, $staff['pm']);

        $this->milestone($project, 'Lab reports live at Karve Road', '2026-06-15', MilestoneStatus::InProgress, 70);
        $this->requirement($project, 'ABHA-ready patient login', RequirementStatus::InProgress, RequirementApprovalStatus::Pending, ProjectPriority::Urgent);

        $this->task($project, $portal, $staff['dev'], 'Lab PDF download with watermark', TaskStatus::Review, '2026-07-08');
        $this->task($project, $portal, $staff['intern'], 'Appointment reminder WhatsApp copy', TaskStatus::InProgress, '2026-07-20');
        $this->task($project, $portal, $staff['qa'], 'Pune OPD booking smoke test', TaskStatus::Todo, '2026-07-25');

        ChangeRequest::query()->firstOrCreate(
            ['number' => 'CR-2026-0001'],
            [
                'project_id' => $project->id,
                'requested_by_id' => $staff['pm']->id,
                'title' => 'Add Marathi UI for OPD kiosk',
                'description' => 'Front-desk kiosks at Karve Road need Marathi labels beside English.',
                'impact_on_cost' => 85000,
                'impact_on_timeline_days' => 10,
                'status' => ChangeRequestStatus::Pending,
                'notes' => 'Raised after the April HIS workshop.',
            ],
        );
    }

    /**
     * @param  array<string, User>  $staff
     * @param  array<string, Client>  $clients
     * @param  array<string, Project>  $projects
     * @param  array<string, Quotation>  $quotations
     */
    protected function seedFinance(array $staff, array $clients, array $projects, array $quotations, QuotationCalculator $calculator): void
    {
        $paid = $this->invoice($staff, $clients['nimbus'], $projects['nimbus'], $quotations['nimbus'], [
            'number' => 'INV-2026-0001',
            'title' => 'Nimbus — discovery milestone',
            'invoice_date' => '2026-01-20',
            'due_date' => '2026-02-04',
            'status' => InvoiceStatus::Paid,
            'sent_at' => '2026-01-20 10:00:00',
            'paid_at' => '2026-02-02 16:40:00',
            'items' => [['Nimbus discovery and process mapping', 180000]],
        ], $calculator, paid: true);

        $this->payment($paid, $staff['finance'], $paid->total, '2026-02-02', PaymentMethod::BankTransfer, 'HDFC-NIM-90211');

        $partial = $this->invoice($staff, $clients['nimbus'], $projects['nimbus'], $quotations['nimbus'], [
            'number' => 'INV-2026-0002',
            'title' => 'Nimbus — storefront build (40%)',
            'invoice_date' => '2026-03-15',
            'due_date' => '2026-03-30',
            'status' => InvoiceStatus::PartiallyPaid,
            'sent_at' => '2026-03-15 11:00:00',
            'items' => [['Storefront build — 40% milestone', 392000]],
        ], $calculator, paidAmount: 200000);

        $this->payment($partial, $staff['finance'], 200000, '2026-03-28', PaymentMethod::Upi, 'UPI-NIM-44021');

        $this->invoice($staff, $clients['sahyadri'], $projects['sahyadri'], $quotations['sahyadri'], [
            'number' => 'INV-2026-0003',
            'title' => 'Sahyadri — HIS integration',
            'invoice_date' => '2026-04-20',
            'due_date' => '2026-05-05',
            'status' => InvoiceStatus::Overdue,
            'sent_at' => '2026-04-20 09:15:00',
            'items' => [['HIS integration milestone', 680000]],
        ], $calculator);

        Expense::query()->firstOrCreate(
            ['number' => 'EXP-2026-0001'],
            [
                'project_id' => $projects['nimbus']->id,
                'recorded_by_id' => $staff['finance']->id,
                'expense_date' => '2026-03-04',
                'category' => 'travel',
                'vendor' => 'IRCTC / Taj Lands End',
                'amount' => 18500,
                'tax_amount' => 3330,
                'payment_method' => PaymentMethod::Card,
                'status' => ExpenseStatus::Paid,
                'notes' => 'Mumbai client workshop — Vikram and Karthik.',
            ],
        );

        Expense::query()->firstOrCreate(
            ['number' => 'EXP-2026-0002'],
            [
                'recorded_by_id' => $staff['finance']->id,
                'expense_date' => '2026-10-01',
                'category' => 'software',
                'vendor' => 'Amazon Web Services India',
                'amount' => 42000,
                'tax_amount' => 7560,
                'payment_method' => PaymentMethod::BankTransfer,
                'status' => ExpenseStatus::Approved,
                'notes' => 'October hosting for client staging.',
            ],
        );
    }

    /**
     * @param  array<string, User>  $staff
     * @param  array<string, mixed>  $row
     */
    protected function invoice(array $staff, Client $client, Project $project, Quotation $quotation, array $row, QuotationCalculator $calculator, bool $paid = false, float $paidAmount = 0): Invoice
    {
        $totals = $calculator->calculate(
            collect($row['items'])->map(fn (array $item) => [
                'description' => $item[0],
                'quantity' => 1,
                'unit_price' => $item[1],
                'discount_percent' => 0,
                'tax_percent' => 18,
            ])->all(),
        );

        $amountPaid = $paid ? $totals['total'] : $paidAmount;
        $balance = round($totals['total'] - $amountPaid, 2);

        $invoice = Invoice::query()->updateOrCreate(
            ['number' => $row['number']],
            [
                'client_id' => $client->id,
                'project_id' => $project->id,
                'quotation_id' => $quotation->id,
                'created_by_id' => $staff['finance']->id,
                'title' => $row['title'],
                'invoice_date' => $row['invoice_date'],
                'due_date' => $row['due_date'],
                'status' => $row['status'],
                'subtotal' => $totals['subtotal'],
                'discount_percent' => 0,
                'discount_amount' => $totals['discount_amount'],
                'tax_percent' => 18,
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'amount_paid' => $amountPaid,
                'balance' => $balance,
                'payment_terms' => 'net_15',
                'sent_at' => $row['sent_at'] ?? null,
                'paid_at' => $row['paid_at'] ?? null,
                'notes' => 'GST invoice in INR.',
            ],
        );

        if ($invoice->items()->count() === 0) {
            foreach ($totals['items'] as $item) {
                $invoice->items()->create($item);
            }
        }

        return $invoice;
    }

    protected function payment(Invoice $invoice, User $finance, float $amount, string $paidOn, PaymentMethod $method, string $reference): void
    {
        Payment::query()->firstOrCreate(
            ['reference' => $reference],
            [
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'recorded_by_id' => $finance->id,
                'amount' => $amount,
                'paid_on' => $paidOn,
                'method' => $method,
                'notes' => 'Received in Dvmsoft current account.',
            ],
        );
    }

    /**
     * @param  array<string, User>  $staff
     * @param  array<string, Client>  $clients
     * @param  array<string, Project>  $projects
     */
    protected function seedSupport(array $staff, array $clients, array $projects): void
    {
        $technical = TicketCategory::query()->where('slug', 'technical')->first();
        $billing = TicketCategory::query()->where('slug', 'billing')->first();
        $nimbusPortal = ClientUser::query()->where('email', 'ravi.kulkarni@nimbusretail.in')->first();
        $sahyadriPortal = ClientUser::query()->where('email', 'nikhil.kulkarni@sahyadricare.in')->first();

        $open = Ticket::query()->updateOrCreate(
            ['number' => 'TKT-2026-0001'],
            [
                'client_id' => $clients['nimbus']->id,
                'project_id' => $projects['nimbus']->id,
                'category_id' => $technical?->id,
                'assigned_to_id' => $staff['support']->id,
                'created_by_id' => $staff['support']->id,
                'client_user_id' => $nimbusPortal?->id,
                'subject' => 'UPI callback failing on festive sale checkout',
                'description' => 'Razorpay success callback is not marking Mumbai store orders as paid after 8pm IST.',
                'priority' => TicketPriority::High,
                'status' => TicketStatus::InProgress,
                'sla_due_at' => now()->addHours(6),
            ],
        );

        $this->ticketMessage($open, $nimbusPortal?->id, null, 'Orders after 8pm stay in pending even though the customer is charged. Happening at Kalina and Andheri.', false);
        $this->ticketMessage($open, null, $staff['support']->id, 'Reproduced on staging. Checking webhook retries with Karthik.', false);
        $this->ticketMessage($open, null, $staff['dev']->id, 'Razorpay IST timezone on the job queue. Patch going to staging tonight.', true);

        $waiting = Ticket::query()->updateOrCreate(
            ['number' => 'TKT-2026-0002'],
            [
                'client_id' => $clients['sahyadri']->id,
                'project_id' => $projects['sahyadri']->id,
                'category_id' => $billing?->id,
                'assigned_to_id' => $staff['support']->id,
                'created_by_id' => $staff['finance']->id,
                'client_user_id' => $sahyadriPortal?->id,
                'subject' => 'Need GST invoice copy for April HIS milestone',
                'description' => 'Accounts at Karve Road need INV-2026-0003 with HSN for their CA.',
                'priority' => TicketPriority::Normal,
                'status' => TicketStatus::WaitingForClient,
                'sla_due_at' => now()->addHours(20),
            ],
        );

        $this->ticketMessage($waiting, $sahyadriPortal?->id, null, 'Please share the GST invoice PDF to accounts@sahyadricare.in.', false);

        Ticket::query()->updateOrCreate(
            ['number' => 'TKT-2026-0003'],
            [
                'client_id' => $clients['nimbus']->id,
                'project_id' => $projects['nimbus']->id,
                'category_id' => $technical?->id,
                'assigned_to_id' => $staff['support']->id,
                'created_by_id' => $staff['support']->id,
                'subject' => 'Marathi labels missing on pick-list print',
                'description' => 'Warehouse printers at Bhiwandi still print English-only SKUs.',
                'priority' => TicketPriority::Low,
                'status' => TicketStatus::Resolved,
                'resolution' => 'Shipped bilingual print template on 12 Oct.',
                'resolved_at' => '2026-10-12 17:20:00',
                'closed_at' => '2026-10-13 11:00:00',
                'sla_due_at' => '2026-10-13 12:00:00',
            ],
        );
    }

    /**
     * @param  array<string, User>  $staff
     * @param  array<string, Client>  $clients
     * @param  array<string, Project>  $projects
     * @param  array<string, Quotation>  $quotations
     */
    protected function seedDocuments(array $staff, array $clients, array $projects, array $quotations): void
    {
        $contract = DocumentType::query()->where('slug', 'contract')->first();
        $policy = DocumentType::query()->where('slug', 'policy')->first();
        $finance = DocumentType::query()->where('slug', 'finance')->first();

        Document::query()->firstOrCreate(
            ['number' => 'DOC-2026-0001'],
            [
                'title' => 'Nimbus Retail master services agreement',
                'document_type_id' => $contract?->id,
                'description' => 'Signed MSA covering the storefront programme.',
                'status' => DocumentStatus::Signed,
                'current_version_number' => 1,
                'owner_id' => $staff['sales']->id,
                'created_by_id' => $staff['admin']->id,
                'client_id' => $clients['nimbus']->id,
                'project_id' => $projects['nimbus']->id,
                'quotation_id' => $quotations['nimbus']->id,
                'approved_by_id' => $staff['admin']->id,
                'approved_at' => '2025-11-18 12:00:00',
            ],
        );

        Document::query()->firstOrCreate(
            ['number' => 'DOC-2026-0002'],
            [
                'title' => 'Leave and holiday policy FY 2026-27',
                'document_type_id' => $policy?->id,
                'description' => 'Office holidays aligned to the India calendar.',
                'status' => DocumentStatus::Approved,
                'current_version_number' => 1,
                'owner_id' => $staff['hr']->id,
                'created_by_id' => $staff['hr']->id,
                'approved_by_id' => $staff['admin']->id,
                'approved_at' => '2026-01-05 10:00:00',
            ],
        );

        Document::query()->firstOrCreate(
            ['number' => 'DOC-2026-0003'],
            [
                'title' => 'Sahyadri Care tax invoice pack',
                'document_type_id' => $finance?->id,
                'description' => 'GST invoice copies for April HIS milestone.',
                'status' => DocumentStatus::Sent,
                'current_version_number' => 1,
                'owner_id' => $staff['finance']->id,
                'created_by_id' => $staff['finance']->id,
                'client_id' => $clients['sahyadri']->id,
                'project_id' => $projects['sahyadri']->id,
            ],
        );
    }

    /**
     * @param  array<string, User>  $staff
     * @param  array<string, Lead>  $leads
     * @param  array<string, Client>  $clients
     */
    protected function seedFollowUps(array $staff, array $leads, array $clients): void
    {
        $this->followUp($leads['meenakshi'], $staff['executive'], now()->addDays(2)->setTime(11, 0), FollowUpType::Call, FollowUpStatus::Pending, 'Call Lakshmi about the Coimbatore POD pilot.');
        $this->followUp($leads['jaipur'], $staff['executive_west'], now()->addDay()->setTime(16, 30), FollowUpType::Whatsapp, FollowUpStatus::Pending, 'Share LMS screenshots with Aman.');
        $this->followUp($leads['delhi'], $staff['sales'], now()->addDays(3)->setTime(15, 0), FollowUpType::Meeting, FollowUpStatus::Pending, 'Proposal walkthrough at Connaught Place office.');
        $this->followUp($clients['sahyadri'], $staff['sales'], now()->subDays(2)->setTime(10, 0), FollowUpType::Email, FollowUpStatus::Completed, 'Chased April GST invoice with accounts.', completed: true);
    }

    /**
     * @param  array<string, User>  $staff
     * @param  array<string, Project>  $projects
     */
    protected function seedWork(array $staff, array $projects): void
    {
        WorkGoal::query()->firstOrCreate(
            ['assigned_user_id' => $staff['dev']->id, 'title' => 'Ship Nimbus UPI retry queue'],
            [
                'created_by' => $staff['pm']->id,
                'project_id' => $projects['nimbus']->id,
                'description' => 'Stabilise Razorpay callbacks for Maharashtra stores.',
                'expected_outcome' => 'Zero pending paid orders after 8pm IST.',
                'start_date' => now()->startOfWeek()->toDateString(),
                'due_date' => now()->addDays(10)->toDateString(),
                'priority' => WorkPriority::Urgent,
                'status' => WorkGoalStatus::InProgress,
                'progress' => 55,
            ],
        );

        WorkGoal::query()->firstOrCreate(
            ['assigned_user_id' => $staff['intern']->id, 'title' => 'WhatsApp appointment copy for Sahyadri'],
            [
                'created_by' => $staff['pm']->id,
                'project_id' => $projects['sahyadri']->id,
                'description' => 'Draft bilingual reminders for OPD bookings.',
                'expected_outcome' => 'Approved English and Marathi templates.',
                'start_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'priority' => WorkPriority::Medium,
                'status' => WorkGoalStatus::InProgress,
                'progress' => 30,
            ],
        );

        WorkLearningGoal::query()->firstOrCreate(
            ['assigned_user_id' => $staff['intern']->id, 'title' => 'Livewire 4 forms and file uploads'],
            [
                'created_by' => $staff['pm']->id,
                'description' => 'Practise staff form patterns used on Nimbus admin.',
                'target_date' => now()->addMonth()->toDateString(),
                'status' => WorkLearningStatus::Learning,
            ],
        );

        $days = [
            now()->subDays(2)->toDateString() => [
                'accomplished' => 'Finished GST PDF in English for Nimbus Bhiwandi warehouse.',
                'pending' => 'Marathi labels on the pick-list.',
                'status' => WorkDailyUpdateStatus::Done,
                'stamp' => WorkManagerStamp::Good,
            ],
            now()->subDay()->toDateString() => [
                'accomplished' => 'Razorpay webhook retries on staging; reproduced the 8pm IST miss.',
                'pending' => 'Production deploy after Priya confirms the festive window.',
                'blocked' => 'Waiting on Razorpay dashboard access from Nimbus IT.',
                'status' => WorkDailyUpdateStatus::Done,
                'stamp' => WorkManagerStamp::NeedsFollowUp,
            ],
            now()->toDateString() => [
                'accomplished' => 'Patched queue timezone and wrote the regression checklist with Aditi.',
                'pending' => 'Monitor first live sale tonight.',
                'status' => WorkDailyUpdateStatus::InReview,
            ],
        ];

        foreach ($days as $date => $row) {
            if (Carbon::parse($date)->isWeekend()) {
                continue;
            }

            WorkDailyUpdate::query()->firstOrCreate(
                ['user_id' => $staff['dev']->id, 'work_date' => $date],
                [
                    'accomplished' => $row['accomplished'],
                    'pending' => $row['pending'] ?? null,
                    'blocked' => $row['blocked'] ?? null,
                    'learned' => 'Razorpay IST vs UTC on Horizon.',
                    'submitted_at' => Carbon::parse($date)->setTime(18, 30),
                    'status' => $row['status'],
                    'reviewed_by' => ($row['status'] === WorkDailyUpdateStatus::Done) ? $staff['pm']->id : null,
                    'reviewed_at' => ($row['status'] === WorkDailyUpdateStatus::Done) ? Carbon::parse($date)->setTime(19, 15) : null,
                    'manager_stamp' => $row['stamp'] ?? null,
                ],
            );
        }
    }

    protected function stage(
        Project $project,
        int $sequence,
        string $name,
        StageStatus $status,
        string $start,
        string $due,
        int $progress,
        User $owner,
        ?string $completedAt = null,
    ): ProjectStage {
        return ProjectStage::query()->updateOrCreate(
            ['project_id' => $project->id, 'sequence' => $sequence],
            [
                'name' => $name,
                'description' => $name.' for '.$project->name,
                'status' => $status,
                'start_date' => $start,
                'due_date' => $due,
                'completion_percentage' => $progress,
                'owner_id' => $owner->id,
                'client_review_enabled' => true,
                'approval_requirement' => StageApprovalRequirement::Inherit,
                'requires_evidence' => false,
                'completed_at' => $completedAt,
            ],
        );
    }

    protected function milestone(Project $project, string $name, string $due, MilestoneStatus $status, int $percent, ?string $completedAt = null): void
    {
        Milestone::query()->firstOrCreate(
            ['project_id' => $project->id, 'name' => $name],
            [
                'due_date' => $due,
                'status' => $status,
                'completion_percentage' => $percent,
                'completed_at' => $completedAt,
            ],
        );
    }

    protected function requirement(
        Project $project,
        string $title,
        RequirementStatus $status,
        RequirementApprovalStatus $approval,
        ProjectPriority $priority,
    ): void {
        Requirement::query()->firstOrCreate(
            ['project_id' => $project->id, 'title' => $title],
            [
                'description' => $title,
                'status' => $status,
                'client_approval_status' => $approval,
                'priority' => $priority,
            ],
        );
    }

    protected function task(Project $project, ProjectStage $stage, User $assignee, string $title, TaskStatus $status, string $due): void
    {
        Task::query()->firstOrCreate(
            ['project_id' => $project->id, 'title' => $title],
            [
                'stage_id' => $stage->id,
                'assigned_user_id' => $assignee->id,
                'description' => $title,
                'priority' => ProjectPriority::Medium,
                'status' => $status,
                'start_date' => $stage->start_date?->toDateString(),
                'due_date' => $due,
                'estimated_hours' => 16,
                'completed_at' => $status === TaskStatus::Completed ? $due.' 18:00:00' : null,
            ],
        );
    }

    protected function ticketMessage(Ticket $ticket, ?string $clientUserId, ?string $authorId, string $body, bool $internal): void
    {
        TicketMessage::query()->firstOrCreate(
            ['ticket_id' => $ticket->id, 'body' => $body],
            [
                'author_id' => $authorId,
                'client_user_id' => $clientUserId,
                'is_internal' => $internal,
            ],
        );
    }

    protected function followUp(Lead|Client $subject, User $assignee, Carbon $when, FollowUpType $type, FollowUpStatus $status, string $notes, bool $completed = false): void
    {
        FollowUp::query()->firstOrCreate(
            [
                'followable_type' => $subject->getMorphClass(),
                'followable_id' => $subject->id,
                'scheduled_at' => $when->format('Y-m-d H:i:s'),
            ],
            [
                'assigned_user_id' => $assignee->id,
                'type' => $type,
                'notes' => $notes,
                'status' => $status,
                'reminder_at' => $when->copy()->subHour(),
                'completed_at' => $completed ? $when : null,
            ],
        );
    }

    protected function leave(
        string $employeeId,
        string $typeId,
        string $requestedBy,
        ?string $approverId,
        string $start,
        string $end,
        float $days,
        string $reason,
        LeaveRequestStatus $status,
    ): void {
        LeaveRequest::query()->firstOrCreate(
            ['employee_id' => $employeeId, 'start_date' => $start],
            [
                'leave_type_id' => $typeId,
                'requested_by_id' => $requestedBy,
                'approver_id' => $approverId,
                'end_date' => $end,
                'days' => $days,
                'reason' => $reason,
                'status' => $status,
                'decided_at' => $status === LeaveRequestStatus::Approved ? $start.' 10:00:00' : null,
            ],
        );
    }
}
