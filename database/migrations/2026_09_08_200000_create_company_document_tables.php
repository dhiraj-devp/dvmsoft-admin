<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_document_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('company_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('number')->unique();
            $table->string('title');
            $table->foreignUlid('document_type_id')->constrained('company_document_types')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('current_version_number')->default(1);
            $table->foreignUlid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->timestamp('expiry_notified_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignUlid('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignUlid('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignUlid('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('company_document_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('document_id')->constrained('company_documents')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('version_label');
            $table->string('original_name');
            $table->string('path');
            $table->string('disk')->default('documents');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignUlid('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('change_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['document_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_document_versions');
        Schema::dropIfExists('company_documents');
        Schema::dropIfExists('company_document_types');
    }
};
