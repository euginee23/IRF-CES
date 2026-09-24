<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_messages', function (Blueprint $table) {
            $table->id();

            // Polymorphic: quote requests today, job orders next, without a
            // second table.
            $table->morphs('messageable');

            $table->string('channel'); // email | sms

            // The address actually used, kept verbatim: phone numbers are
            // stored in mixed formats, so recording the normalised E.164 that
            // went to the provider is what makes a delivery dispute answerable.
            $table->string('recipient');

            $table->string('subject')->nullable(); // email only
            $table->text('body');

            // Which preset was used, or null when staff wrote it from scratch.
            $table->string('template')->nullable();

            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();

            // morphs() already indexes (messageable_type, messageable_id),
            // which is what the per-record history list queries on.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_messages');
    }
};
