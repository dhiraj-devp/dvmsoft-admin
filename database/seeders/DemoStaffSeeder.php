<?php

namespace Database\Seeders;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\ProbationStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DemoStaffSeeder extends Seeder
{
    use WithoutModelEvents;

    public const PASSWORD = 'password';

    public function run(): void
    {
        $departments = Department::query()->get()->keyBy('slug');
        $roles = Role::query()->get()->keyBy('slug');
        $officeAdmin = User::query()->where('email', SuperAdminSeeder::ADMIN_EMAIL)->first();

        foreach ($this->people() as $person) {
            $joining = Carbon::parse($person['joined']);
            $department = $departments->get($person['department']);
            $role = $roles->get($person['role']);
            $manager = isset($person['manager_email'])
                ? User::query()->where('email', $person['manager_email'])->first()
                : $officeAdmin;

            $user = User::query()->firstOrCreate(
                ['email' => $person['email']],
                [
                    'name' => $person['name'],
                    'password' => self::PASSWORD,
                    'phone' => $person['phone'],
                    'employee_code' => $person['employee_code'],
                    'job_title' => $person['job_title'],
                    'department_id' => $department?->id,
                    'manager_id' => $manager?->id,
                    'date_of_joining' => $joining->toDateString(),
                    'is_active' => true,
                    'is_super_admin' => false,
                    'theme' => 'system',
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ],
            );

            $user->fill([
                'name' => $person['name'],
                'phone' => $person['phone'],
                'job_title' => $person['job_title'],
                'department_id' => $department?->id,
                'manager_id' => $manager?->id,
                'date_of_joining' => $joining->toDateString(),
            ]);

            if (blank($user->employee_code)) {
                $user->employee_code = $person['employee_code'];
            }

            $user->save();

            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }

            $onProbation = $person['employment_status'] === EmploymentStatus::Probation;

            Employee::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'employment_type' => $person['employment_type'],
                    'employment_status' => $person['employment_status'],
                    'probation_days' => 90,
                    'probation_status' => $onProbation ? ProbationStatus::Ongoing : ProbationStatus::Confirmed,
                    'probation_end_date' => $joining->copy()->addDays(90)->toDateString(),
                    'confirmation_date' => $onProbation ? null : $joining->copy()->addDays(90)->toDateString(),
                    'address' => $person['address'],
                    'emergency_contact_name' => $person['emergency_name'],
                    'emergency_contact_phone' => $person['emergency_phone'],
                    'photo_disk' => 'hr',
                ],
            );
        }
    }

    /**
     * @return list<array{
     *     name: string,
     *     email: string,
     *     phone: string,
     *     employee_code: string,
     *     job_title: string,
     *     department: string,
     *     role: string,
     *     manager_email?: string,
     *     joined: string,
     *     employment_type: EmploymentType,
     *     employment_status: EmploymentStatus,
     *     address: string,
     *     emergency_name: string,
     *     emergency_phone: string
     * }>
     */
    protected function people(): array
    {
        $office = SuperAdminSeeder::ADMIN_EMAIL;

        return [
            [
                'name' => 'Kavya Singh',
                'email' => 'kavya.singh@dvmsoft.local',
                'phone' => '+91 98100 11223',
                'employee_code' => 'DVM-0101',
                'job_title' => 'HR Manager',
                'department' => 'hr',
                'role' => 'hr-manager',
                'manager_email' => $office,
                'joined' => '2020-06-15',
                'employment_type' => EmploymentType::FullTime,
                'employment_status' => EmploymentStatus::Active,
                'address' => 'B-14, Greater Kailash I, New Delhi 110048',
                'emergency_name' => 'Rajesh Singh',
                'emergency_phone' => '+91 98100 11224',
            ],
            [
                'name' => 'Priya Sharma',
                'email' => 'priya.sharma@dvmsoft.local',
                'phone' => '+91 98711 44556',
                'employee_code' => 'DVM-0102',
                'job_title' => 'Sales Manager',
                'department' => 'sales',
                'role' => 'sales-manager',
                'manager_email' => $office,
                'joined' => '2019-11-04',
                'employment_type' => EmploymentType::FullTime,
                'employment_status' => EmploymentStatus::Active,
                'address' => '42, Sector 43, Gurugram, Haryana 122002',
                'emergency_name' => 'Amit Sharma',
                'emergency_phone' => '+91 98711 44557',
            ],
            [
                'name' => 'Vikram Iyer',
                'email' => 'vikram.iyer@dvmsoft.local',
                'phone' => '+91 98450 77889',
                'employee_code' => 'DVM-0103',
                'job_title' => 'Project Manager',
                'department' => 'project-management',
                'role' => 'project-manager',
                'manager_email' => $office,
                'joined' => '2018-08-20',
                'employment_type' => EmploymentType::FullTime,
                'employment_status' => EmploymentStatus::Active,
                'address' => '12, Koramangala 4th Block, Bengaluru, Karnataka 560034',
                'emergency_name' => 'Lakshmi Iyer',
                'emergency_phone' => '+91 98450 77890',
            ],
            [
                'name' => 'Rohan Verma',
                'email' => 'rohan.verma@dvmsoft.local',
                'phone' => '+91 98991 22334',
                'employee_code' => 'DVM-0104',
                'job_title' => 'Support Executive',
                'department' => 'support',
                'role' => 'support-executive',
                'manager_email' => $office,
                'joined' => '2023-02-13',
                'employment_type' => EmploymentType::FullTime,
                'employment_status' => EmploymentStatus::Active,
                'address' => 'C-9, Sector 62, Noida, Uttar Pradesh 201309',
                'emergency_name' => 'Meena Verma',
                'emergency_phone' => '+91 98991 22335',
            ],
            [
                'name' => 'Anjali Nair',
                'email' => 'anjali.nair@dvmsoft.local',
                'phone' => '+91 98470 55667',
                'employee_code' => 'DVM-0105',
                'job_title' => 'Accountant',
                'department' => 'finance',
                'role' => 'accountant',
                'manager_email' => $office,
                'joined' => '2021-01-18',
                'employment_type' => EmploymentType::FullTime,
                'employment_status' => EmploymentStatus::Active,
                'address' => '7/12, Panampilly Nagar, Kochi, Kerala 682036',
                'emergency_name' => 'Suresh Nair',
                'emergency_phone' => '+91 98470 55668',
            ],
            [
                'name' => 'Arjun Mehta',
                'email' => 'arjun.mehta@dvmsoft.local',
                'phone' => '+91 98200 33445',
                'employee_code' => 'DVM-0106',
                'job_title' => 'Sales Executive',
                'department' => 'sales',
                'role' => 'sales-executive',
                'manager_email' => 'priya.sharma@dvmsoft.local',
                'joined' => '2022-07-11',
                'employment_type' => EmploymentType::FullTime,
                'employment_status' => EmploymentStatus::Active,
                'address' => 'A-204, Palm Court, Andheri West, Mumbai, Maharashtra 400053',
                'emergency_name' => 'Nisha Mehta',
                'emergency_phone' => '+91 98200 33446',
            ],
            [
                'name' => 'Neha Patel',
                'email' => 'neha.patel@dvmsoft.local',
                'phone' => '+91 97244 66778',
                'employee_code' => 'DVM-0107',
                'job_title' => 'Sales Executive',
                'department' => 'sales',
                'role' => 'sales-executive',
                'manager_email' => 'priya.sharma@dvmsoft.local',
                'joined' => '2024-03-04',
                'employment_type' => EmploymentType::FullTime,
                'employment_status' => EmploymentStatus::Active,
                'address' => '19, Satellite Road, Ahmedabad, Gujarat 380015',
                'emergency_name' => 'Harsh Patel',
                'emergency_phone' => '+91 97244 66779',
            ],
            [
                'name' => 'Karthik Reddy',
                'email' => 'karthik.reddy@dvmsoft.local',
                'phone' => '+91 90000 88990',
                'employee_code' => 'DVM-0108',
                'job_title' => 'Software Developer',
                'department' => 'development',
                'role' => 'developer',
                'manager_email' => 'vikram.iyer@dvmsoft.local',
                'joined' => '2021-09-06',
                'employment_type' => EmploymentType::FullTime,
                'employment_status' => EmploymentStatus::Active,
                'address' => 'Plot 8, Gachibowli, Hyderabad, Telangana 500032',
                'emergency_name' => 'Srinivas Reddy',
                'emergency_phone' => '+91 90000 88991',
            ],
            [
                'name' => 'Ananya Das',
                'email' => 'ananya.das@dvmsoft.local',
                'phone' => '+91 98300 11223',
                'employee_code' => 'DVM-0109',
                'job_title' => 'Software Developer',
                'department' => 'development',
                'role' => 'developer',
                'manager_email' => 'vikram.iyer@dvmsoft.local',
                'joined' => '2023-05-22',
                'employment_type' => EmploymentType::FullTime,
                'employment_status' => EmploymentStatus::Active,
                'address' => 'Flat 3B, Salt Lake Sector V, Kolkata, West Bengal 700091',
                'emergency_name' => 'Debashish Das',
                'emergency_phone' => '+91 98300 11224',
            ],
            [
                'name' => 'Sneha Deshpande',
                'email' => 'sneha.deshpande@dvmsoft.local',
                'phone' => '+91 98810 44556',
                'employee_code' => 'DVM-0110',
                'job_title' => 'UI Designer',
                'department' => 'design',
                'role' => 'designer',
                'manager_email' => 'vikram.iyer@dvmsoft.local',
                'joined' => '2022-01-17',
                'employment_type' => EmploymentType::FullTime,
                'employment_status' => EmploymentStatus::Active,
                'address' => '11, Baner Road, Pune, Maharashtra 411045',
                'emergency_name' => 'Prakash Deshpande',
                'emergency_phone' => '+91 98810 44557',
            ],
            [
                'name' => 'Aditi Rao',
                'email' => 'aditi.rao@dvmsoft.local',
                'phone' => '+91 98860 77889',
                'employee_code' => 'DVM-0111',
                'job_title' => 'QA Engineer',
                'department' => 'qa',
                'role' => 'qa',
                'manager_email' => 'vikram.iyer@dvmsoft.local',
                'joined' => '2022-11-28',
                'employment_type' => EmploymentType::FullTime,
                'employment_status' => EmploymentStatus::Active,
                'address' => '108, Indiranagar 12th Main, Bengaluru, Karnataka 560038',
                'emergency_name' => 'Sanjay Rao',
                'emergency_phone' => '+91 98860 77890',
            ],
            [
                'name' => 'Divya Krishnan',
                'email' => 'divya.krishnan@dvmsoft.local',
                'phone' => '+91 94440 22334',
                'employee_code' => 'DVM-0112',
                'job_title' => 'Software Developer Intern',
                'department' => 'development',
                'role' => 'developer',
                'manager_email' => 'vikram.iyer@dvmsoft.local',
                'joined' => '2026-08-03',
                'employment_type' => EmploymentType::Intern,
                'employment_status' => EmploymentStatus::Probation,
                'address' => '24, Besant Nagar, Chennai, Tamil Nadu 600090',
                'emergency_name' => 'Ramesh Krishnan',
                'emergency_phone' => '+91 94440 22335',
            ],
        ];
    }
}
