<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('converted_client_id')->nullable()->index();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('source')->nullable()->index();
            $table->text('requirement')->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->string('status')->default('new')->index();
            $table->string('priority')->default('medium')->index();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->string('lost_reason')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
