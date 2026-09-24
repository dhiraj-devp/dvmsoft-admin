<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ticket_sla_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('priority')->unique();
            $table->unsignedInteger('hours');
            $table->unsignedInteger('warning_hours')->default(1);
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('number')->unique();
            $table->foreignUlid('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignUlid('category_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
            $table->foreignUlid('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->string('priority')->default('normal')->index();
            $table->string('status')->default('open')->index();
            $table->timestamp('sla_due_at')->nullable()->index();
            $table->timestamp('sla_breached_at')->nullable();
            $table->timestamp('sla_notified_approaching_at')->nullable();
            $table->timestamp('sla_notified_breached_at')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignUlid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('ticket_message_id')->constrained('ticket_messages')->cascadeOnDelete();
            $table->foreignUlid('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('disk')->default('support');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_sla_rules');
        Schema::dropIfExists('ticket_categories');
    }
};
