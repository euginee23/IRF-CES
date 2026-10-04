<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A quote can now be disapproved as well as approved. When and why are
     * kept on the job order, next to approved_by_customer_at, so the counter
     * sees the customer's reason without digging through the history.
     */
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->timestamp('declined_at')->nullable()->after('approval_method');
            $table->text('decline_reason')->nullable()->after('declined_at');
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropColumn(['declined_at', 'decline_reason']);
        });
    }
};
