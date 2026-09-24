<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->string('theme')->default('system');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'is_active']);
        });

        Schema::create('client_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignUlid('client_user_id')->nullable()->after('user_id')->constrained('client_users')->nullOnDelete();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignUlid('client_user_id')->nullable()->after('created_by_id')->constrained('client_users')->nullOnDelete();
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->foreignUlid('client_user_id')->nullable()->after('author_id')->constrained('client_users')->nullOnDelete();
        });

        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->foreignUlid('client_user_id')->nullable()->after('uploaded_by_id')->constrained('client_users')->nullOnDelete();
        });

        Schema::table('change_requests', function (Blueprint $table) {
            $table->foreignUlid('client_user_id')->nullable()->after('requested_by_id')->constrained('client_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_user_id');
        });

        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_user_id');
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_user_id');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_user_id');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_user_id');
        });

        Schema::dropIfExists('client_password_reset_tokens');
        Schema::dropIfExists('client_users');
    }
};
