<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->string('portal_token', 64)->unique()->nullable()->after('status');
            $table->timestamp('approved_by_customer_at')->nullable()->after('completed_at');
            $table->string('approval_method', 20)->nullable()->after('approved_by_customer_at'); // 'customer' or 'manual'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropColumn(['portal_token', 'approved_by_customer_at', 'approval_method']);
        });
    }
};
