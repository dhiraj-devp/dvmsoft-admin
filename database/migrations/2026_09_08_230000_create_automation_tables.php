<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('module');
            $table->string('trigger_type');
            $table->string('schedule')->nullable();
            $table->json('channels')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_ran_at')->nullable();
            $table->string('last_status')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('last_notified_count')->default(0);
            $table->timestamps();
        });

        Schema::create('automation_runs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('automation_id')->constrained('automations')->cascadeOnDelete();
            $table->string('status');
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('notified_count')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['automation_id', 'created_at']);
        });

        Schema::create('automation_dispatches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('automation_key');
            $table->string('subject_type')->default('');
            $table->string('subject_id')->default('');
            $table->string('occurrence_key');
            $table->timestamp('created_at')->nullable();

            $table->unique(['automation_key', 'subject_type', 'subject_id', 'occurrence_key'], 'automation_dispatches_unique');
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulidMorphs('notifiable');
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('automation_dispatches');
        Schema::dropIfExists('automation_runs');
        Schema::dropIfExists('automations');
    }
};
