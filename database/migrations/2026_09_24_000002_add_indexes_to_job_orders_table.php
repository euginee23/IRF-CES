<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every job order list and dashboard filters on status or orders by
     * created_at, and the table was created with neither indexed. The
     * composite carries the common pairing — "orders in status X, newest
     * first" — and the income report's period grouping.
     *
     * assigned_to is deliberately absent: it is a foreign key, and MySQL
     * indexes those for us.
     */
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status']);
        });
    }
};
