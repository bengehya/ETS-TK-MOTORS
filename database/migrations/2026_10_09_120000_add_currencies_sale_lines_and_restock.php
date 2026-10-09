<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_accounts', function (Blueprint $table) {
            $table->index('organization_id', 'cash_accounts_organization_id_index');
        });

        Schema::table('cash_accounts', function (Blueprint $table) {
            $table->dropUnique(['organization_id']);
            $table->string('currency', 3)->default('USD');
        });

        DB::table('cash_accounts')->where(function ($query): void {
            $query->whereNull('currency')->orWhere('currency', '');
        })->update(['currency' => 'USD']);

        Schema::table('cash_accounts', function (Blueprint $table) {
            $table->unique(['organization_id', 'currency']);
        });

        Schema::table('cash_entries', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('direction');
            $table->index(['organization_id', 'currency', 'occurred_at']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('amount');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('reference');
            $table->decimal('amount_received', 12, 2)->nullable()->after('profit');
            $table->decimal('change_given', 12, 2)->default(0)->after('amount_received');
            $table->uuid('client_token')->nullable()->after('change_given');
            $table->unique(['organization_id', 'client_token']);
        });

        DB::table('sales')->update([
            'currency' => 'USD',
            'change_given' => 0,
            'amount_received' => DB::raw('line_total'),
        ]);

        Schema::create('sale_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_sale_price', 12, 2);
            $table->decimal('unit_purchase_cost', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->decimal('cost_total', 12, 2);
            $table->decimal('profit', 12, 2);
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->index(['sale_id', 'position']);
        });

        foreach (DB::table('sales')->orderBy('id')->get() as $sale) {
            DB::table('sale_lines')->insert([
                'sale_id' => $sale->id,
                'product_id' => $sale->product_id,
                'quantity' => $sale->quantity,
                'unit_sale_price' => $sale->unit_sale_price,
                'unit_purchase_cost' => $sale->unit_purchase_cost,
                'line_total' => $sale->line_total,
                'cost_total' => $sale->cost_total,
                'profit' => $sale->profit,
                'position' => 1,
                'created_at' => $sale->created_at,
                'updated_at' => $sale->updated_at,
            ]);
        }

        Schema::table('customer_requests', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('customer_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->string('designation')->nullable()->after('product_id');
            $table->string('designation_key')->nullable()->after('designation');
            $table->index(['organization_id', 'designation_key', 'status']);
        });

        Schema::table('customer_requests', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::create('customer_request_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['customer_request_id', 'id']);
        });

        Schema::create('restock_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('designation')->nullable();
            $table->string('subject_key');
            $table->unsignedInteger('request_count');
            $table->unsignedInteger('total_quantity');
            $table->unsignedInteger('observation_days');
            $table->timestamp('period_started_at');
            $table->timestamp('last_requested_at');
            $table->unsignedInteger('boutique_quantity');
            $table->unsignedInteger('depot_quantity');
            $table->string('priority');
            $table->string('status');
            $table->text('justification')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'subject_key', 'status']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->decimal('cdf_per_usd', 12, 4);
            $table->timestamp('effective_at');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'effective_at']);
        });

        Schema::create('exchanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('reference');
            $table->string('source_currency', 3);
            $table->decimal('source_amount', 12, 2);
            $table->string('destination_currency', 3);
            $table->decimal('destination_amount', 12, 2);
            $table->decimal('rate', 12, 4);
            $table->foreignId('exchange_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_entry_id')->nullable()->constrained('cash_entries')->nullOnDelete();
            $table->foreignId('destination_entry_id')->nullable()->constrained('cash_entries')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique(['organization_id', 'reference']);
        });

        Schema::create('cash_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('previous_declaration_id')->nullable()->constrained('cash_declarations')->nullOnDelete();
            $table->text('note')->nullable();
            $table->decimal('counted_usd', 12, 2)->nullable();
            $table->decimal('counted_cdf', 12, 2)->nullable();
            $table->decimal('book_usd', 12, 2);
            $table->decimal('book_cdf', 12, 2);
            $table->decimal('gap_usd', 12, 2)->nullable();
            $table->decimal('gap_cdf', 12, 2)->nullable();
            $table->decimal('reference_rate', 12, 4)->nullable();
            $table->timestamp('rate_effective_at')->nullable();
            $table->decimal('indicative_usd', 12, 2)->nullable();
            $table->string('status');
            $table->text('correction_reason')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
        });

        Schema::create('cash_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('currency', 3);
            $table->string('direction');
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('cash_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_adjustments');
        Schema::dropIfExists('cash_declarations');
        Schema::dropIfExists('exchanges');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('restock_suggestions');
        Schema::dropIfExists('customer_request_events');

        Schema::table('customer_requests', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropIndex(['organization_id', 'designation_key', 'status']);
            $table->dropColumn(['designation', 'designation_key']);
        });

        Schema::table('customer_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });

        Schema::table('customer_requests', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::dropIfExists('sale_lines');

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'client_token']);
            $table->dropColumn(['currency', 'amount_received', 'change_given', 'client_token']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('cash_entries', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'currency', 'occurred_at']);
            $table->dropColumn('currency');
        });

        Schema::table('cash_accounts', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'currency']);
            $table->dropColumn('currency');
            $table->unique('organization_id');
            $table->dropIndex('cash_accounts_organization_id_index');
        });
    }
};
