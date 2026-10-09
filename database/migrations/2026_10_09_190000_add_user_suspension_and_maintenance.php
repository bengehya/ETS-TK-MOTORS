<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable();
            $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('maintenance_enabled')->default(false);
            $table->timestamp('maintenance_started_at')->nullable();
            $table->foreignId('maintenance_started_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('maintenance_started_by');
            $table->dropColumn(['maintenance_enabled', 'maintenance_started_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suspended_by');
            $table->dropColumn('suspended_at');
        });
    }
};
