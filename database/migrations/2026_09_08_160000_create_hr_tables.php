<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('employment_type')->default('full_time')->index();
            $table->string('employment_status')->default('probation')->index();
            $table->unsignedInteger('probation_days')->default(90);
            $table->string('probation_status')->default('ongoing')->index();
            $table->date('probation_end_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->date('exit_date')->nullable()->index();
            $table->string('exit_reason')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_disk')->default('hr');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUlid('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->string('status')->default('uploaded')->index();
            $table->date('expiry_date')->nullable()->index();
            $table->string('original_name');
            $table->string('path');
            $table->string('disk')->default('hr');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedInteger('days_per_year')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUlid('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->index();
            $table->decimal('entitled', 8, 2)->default(0);
            $table->decimal('used', 8, 2)->default(0);
            $table->decimal('pending', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignUlid('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->foreignUlid('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->decimal('days', 8, 2);
            $table->text('reason')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_assets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignUlid('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('serial_number')->nullable()->index();
            $table->date('assigned_date')->index();
            $table->date('return_date')->nullable()->index();
            $table->string('condition')->default('good');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hr_checklists', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('status')->default('in_progress')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'type']);
        });

        Schema::create('hr_checklist_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('hr_checklist_id')->constrained('hr_checklists')->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->boolean('is_completed')->default(false)->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignUlid('completed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['hr_checklist_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_checklist_items');
        Schema::dropIfExists('hr_checklists');
        Schema::dropIfExists('employee_assets');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employees');
    }
};
