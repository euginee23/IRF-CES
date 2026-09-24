<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the part_usages table.
     *
     * It was built as a consumption log but nothing ever wrote to it, and its
     * foreign key pointed at repair_quote_request_id — it predates job orders
     * entirely. job_order_parts and stock_movements now cover what it was for,
     * and leaving an empty lookalike beside them invites someone writing to
     * the wrong one.
     */
    public function up(): void
    {
        Schema::dropIfExists('part_usages');
    }

    /**
     * Recreated as it was, minus the data — there was never any.
     */
    public function down(): void
    {
        Schema::create('part_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained()->onDelete('cascade');
            $table->foreignId('repair_quote_request_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('quantity_used');
            $table->decimal('cost_per_unit', 10, 2);
            $table->decimal('total_cost', 10, 2);
            $table->foreignId('used_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
};
