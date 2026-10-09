<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->decimal('fee_amount', 12, 2)->default(0)->after('rate');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('actor_role', 32)->nullable()->after('user_id');
            $table->string('result', 32)->nullable()->after('action');
            $table->string('correlation_id', 64)->nullable()->after('subject_id');
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['correlation_id']);
            $table->dropColumn(['actor_role', 'result', 'correlation_id']);
        });

        Schema::table('exchanges', function (Blueprint $table) {
            $table->dropColumn('fee_amount');
        });
    }
};
