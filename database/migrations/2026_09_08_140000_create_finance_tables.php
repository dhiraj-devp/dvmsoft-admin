<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('number')->unique();
            $table->foreignUlid('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignUlid('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignUlid('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->date('invoice_date')->index();
            $table->date('due_date')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_percent', 8, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_percent', 8, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('last_reminded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_percent', 8, 2)->default(0);
            $table->decimal('tax_percent', 8, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->foreignUlid('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignUlid('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('paid_on')->index();
            $table->string('method')->index();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('receipt_disk')->default('local');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('number')->unique();
            $table->foreignUlid('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignUlid('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expense_date')->index();
            $table->string('category')->index();
            $table->string('vendor');
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('draft')->index();
            $table->text('notes')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('receipt_disk')->default('local');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_reminders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('channel')->default('database')->index();
            $table->timestamp('sent_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_reminders');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
