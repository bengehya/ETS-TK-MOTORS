<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_sellable')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'type']);
            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('code');
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->string('category');
            $table->text('description')->nullable();
            $table->decimal('sale_price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->unique(['organization_id', 'barcode']);
            $table->index(['organization_id', 'name']);
            $table->index(['organization_id', 'category']);
            $table->index(['organization_id', 'is_active']);
        });

        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'location_id']);
            $table->index(['organization_id', 'location_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('transfer_group_id')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
            $table->index(['location_id', 'created_at']);
            $table->index(['type']);
            $table->index(['transfer_group_id']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('locations');
    }
};
