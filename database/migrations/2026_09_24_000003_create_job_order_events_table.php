<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The history of a repair: what happened, when, and who did it.
     *
     * Status changes were destructive until now — a job order carried only its
     * current status plus a few timestamps, so "when was this approved" and
     * "why did it sit for a week" had no answer. This table is both the
     * internal audit trail and the customer-facing tracking thread; which one
     * a row belongs to is decided by is_customer_visible.
     *
     * Messages sent to the customer are NOT copied here — customer_messages
     * already records those, and a second copy would be a second version of
     * the truth. The portal timeline merges the two at read time.
     */
    public function up(): void
    {
        Schema::create('job_order_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('job_order_id')->constrained()->cascadeOnDelete();

            // created | status_changed | assigned | note | part_backordered
            // | parts_arrived | payment_received
            $table->string('type');

            // Both null for events that are not a status change.
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();

            // Worded so it can be shown to a customer as-is when the row is
            // visible; staff-only detail belongs in meta.
            $table->string('description');

            // Amounts, part names, ids — whatever the type needs. Read for
            // display only, never queried on.
            $table->json('meta')->nullable();

            $table->boolean('is_customer_visible')->default(false);

            // Null when the customer acted (portal approval) or the system did.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // The internal timeline: one job order, oldest first.
            $table->index(['job_order_id', 'created_at']);

            // The portal timeline, which filters before it sorts.
            $table->index(['job_order_id', 'is_customer_visible', 'created_at'], 'job_order_events_portal_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_order_events');
    }
};
