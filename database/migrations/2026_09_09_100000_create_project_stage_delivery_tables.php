<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('client_approval_mode')->default('flexible')->after('priority');
        });

        Schema::create('project_stages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sequence')->default(1);
            $table->string('status')->default('not_started')->index();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->foreignUlid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('client_review_enabled')->default(true);
            $table->string('approval_requirement')->default('inherit');
            $table->boolean('requires_evidence')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['project_id', 'sequence']);
        });

        Schema::create('project_stage_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('stage_id')->constrained('project_stages')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['stage_id', 'user_id']);
        });

        Schema::create('project_stage_evidence', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('stage_id')->constrained('project_stages')->cascadeOnDelete();
            $table->foreignUlid('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('text')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('path')->nullable();
            $table->string('disk')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('version')->nullable();
            $table->string('visibility')->default('client')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_stage_deliverables', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('stage_id')->constrained('project_stages')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->string('status')->default('pending')->index();
            $table->foreignUlid('completed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignUlid('evidence_id')->nullable()->constrained('project_stage_evidence')->nullOnDelete();
            $table->unsignedInteger('sequence')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_stage_submissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('stage_id')->constrained('project_stages')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('submitted')->index();
            $table->foreignUlid('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('override_incomplete')->default(false);
            $table->foreignUlid('override_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('override_reason')->nullable();
            $table->foreignUlid('decided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('decided_by_client_user_id')->nullable()->constrained('client_users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_comment')->nullable();
            $table->timestamps();
            $table->unique(['stage_id', 'version']);
        });

        Schema::create('project_stage_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('stage_id')->constrained('project_stages')->cascadeOnDelete();
            $table->foreignUlid('parent_id')->nullable()->constrained('project_stage_messages')->nullOnDelete();
            $table->foreignUlid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('client_user_id')->nullable()->constrained('client_users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_stage_message_attachments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('message_id')->constrained('project_stage_messages')->cascadeOnDelete();
            $table->foreignUlid('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('uploaded_by_client_user_id')->nullable();
            $table->foreign('uploaded_by_client_user_id', 'psma_uploaded_by_client_fk')
                ->references('id')
                ->on('client_users')
                ->nullOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('disk')->default('stages');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignUlid('stage_id')->nullable()->after('milestone_id')->constrained('project_stages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stage_id');
        });

        Schema::dropIfExists('project_stage_message_attachments');
        Schema::dropIfExists('project_stage_messages');
        Schema::dropIfExists('project_stage_submissions');
        Schema::dropIfExists('project_stage_deliverables');
        Schema::dropIfExists('project_stage_evidence');
        Schema::dropIfExists('project_stage_members');
        Schema::dropIfExists('project_stages');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('client_approval_mode');
        });
    }
};
