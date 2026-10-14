<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The customer's answer to a quote request, kept beside the quote itself:
     * when they approved or disapproved, why, and whether they answered on
     * the portal or staff recorded it for them. response_seen_at stays empty
     * until a staff member opens the request, which is how the Quotes link
     * knows there is an answer nobody has looked at yet.
     */
    public function up(): void
    {
        Schema::table('repair_quote_requests', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('portal_token');
            $table->timestamp('declined_at')->nullable()->after('approved_at');
            $table->text('decline_reason')->nullable()->after('declined_at');
            $table->string('response_method', 20)->nullable()->after('decline_reason');
            $table->timestamp('response_seen_at')->nullable()->after('response_method');
        });
    }

    public function down(): void
    {
        Schema::table('repair_quote_requests', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'declined_at', 'decline_reason', 'response_method', 'response_seen_at']);
        });
    }
};
