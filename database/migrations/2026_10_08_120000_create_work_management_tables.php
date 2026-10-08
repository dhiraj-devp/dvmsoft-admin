<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_goals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('assigned_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('expected_outcome')->nullable();
            $table->date('start_date')->nullable()->index();
            $table->date('due_date')->nullable()->index();
            $table->string('priority')->default('medium')->index();
            $table->string('status')->default('not_started')->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['assigned_user_id', 'status']);
        });

        Schema::create('work_learning_goals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('assigned_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('target_date')->nullable()->index();
            $table->string('status')->default('not_started')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('work_daily_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('work_date')->index();
            $table->text('focus')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'work_date']);
        });

        Schema::create('work_daily_plan_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('work_daily_plan_id')->constrained('work_daily_plans')->cascadeOnDelete();
            $table->foreignUlid('work_goal_id')->nullable()->constrained('work_goals')->nullOnDelete();
            $table->foreignUlid('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('title');
            $table->string('status')->default('planned')->index();
            $table->text('completion_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('work_daily_updates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('work_daily_plan_id')->nullable()->constrained('work_daily_plans')->nullOnDelete();
            $table->date('work_date')->index();
            $table->text('accomplished');
            $table->text('pending')->nullable();
            $table->text('blocked')->nullable();
            $table->text('learned')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'work_date']);
        });

        Schema::create('work_daily_update_learnings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('work_daily_update_id')->constrained('work_daily_updates')->cascadeOnDelete();
            $table->foreignUlid('work_learning_goal_id')->nullable()->constrained('work_learning_goals')->nullOnDelete();
            $table->string('topic');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->timestamps();
        });

        Schema::create('work_daily_update_evidence', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('work_daily_update_id')->constrained('work_daily_updates')->cascadeOnDelete();
            $table->string('type')->default('url');
            $table->string('label')->nullable();
            $table->text('url')->nullable();
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->timestamps();
        });

        Schema::create('work_reviews', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('work_daily_update_id')->nullable()->unique()->constrained('work_daily_updates')->nullOnDelete();
            $table->unsignedTinyInteger('quality_score');
            $table->text('feedback');
            $table->text('action_items')->nullable();
            $table->date('reviewed_on')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_reviews');
        Schema::dropIfExists('work_daily_update_evidence');
        Schema::dropIfExists('work_daily_update_learnings');
        Schema::dropIfExists('work_daily_updates');
        Schema::dropIfExists('work_daily_plan_items');
        Schema::dropIfExists('work_daily_plans');
        Schema::dropIfExists('work_learning_goals');
        Schema::dropIfExists('work_goals');
    }
};
