<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulidMorphs('followable');
            $table->foreignUlid('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->index();
            $table->string('type')->default('call')->index();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('reminder_at')->nullable()->index();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
