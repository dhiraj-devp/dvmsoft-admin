<?php

namespace Database\Factories;

use App\Enums\EmployeeDocumentStatus;
use App\Enums\EmployeeDocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDocument>
 */
class EmployeeDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'uploaded_by_id' => User::factory(),
            'type' => EmployeeDocumentType::Nda,
            'title' => 'NDA',
            'status' => EmployeeDocumentStatus::Uploaded,
            'original_name' => 'nda.pdf',
            'path' => 'employees/test/nda.pdf',
            'disk' => 'hr',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
