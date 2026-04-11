<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_quote_requests', function (Blueprint $table) {
            $table->decimal('quoted_price', 10, 2)->nullable()->after('status');
            $table->text('quote_notes')->nullable()->after('quoted_price');
            $table->timestamp('quoted_at')->nullable()->after('quote_notes');
            $table->string('portal_token', 64)->nullable()->unique()->after('quoted_at');
        });
    }

    public function down(): void
    {
        Schema::table('repair_quote_requests', function (Blueprint $table) {
            $table->dropColumn(['quoted_price', 'quote_notes', 'quoted_at', 'portal_token']);
        });
    }
};
