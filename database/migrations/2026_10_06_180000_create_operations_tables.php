<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('low_stock_threshold')->default(2);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('reference');
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_sale_price', 12, 2);
            $table->decimal('unit_purchase_cost', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->decimal('cost_total', 12, 2);
            $table->decimal('profit', 12, 2);
            $table->string('status');
            $table->timestamp('sold_at');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'reference']);
            $table->index(['organization_id', 'status', 'sold_at']);
            $table->index(['organization_id', 'seller_id']);
            $table->index(['organization_id', 'product_id']);
        });

        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();

            $table->unique('organization_id');
        });

        Schema::create('cash_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('cash_account_id')->constrained()->restrictOnDelete();
            $table->string('reference');
            $table->string('direction');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('label');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique(['organization_id', 'reference']);
            $table->index(['organization_id', 'occurred_at']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('reference');
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->date('spent_on');
            $table->string('status');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->foreignId('cash_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'reference']);
            $table->index(['organization_id', 'status', 'spent_on']);
        });

        Schema::create('customer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('customer_name')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('priority');
            $table->unsignedInteger('frequency');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamp('requested_at');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('closure_note')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'product_id', 'status']);
            $table->index(['organization_id', 'priority', 'status']);
            $table->index(['organization_id', 'requested_at']);
        });

        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('label');
            $table->decimal('amount', 12, 2);
            $table->date('started_on');
            $table->unsignedInteger('duration_months');
            $table->date('ends_on');
            $table->string('status');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('closure_reason')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'ends_on']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'action']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('rentals');
        Schema::dropIfExists('customer_requests');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('cash_entries');
        Schema::dropIfExists('cash_accounts');
        Schema::dropIfExists('sales');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('low_stock_threshold');
        });
    }
};
