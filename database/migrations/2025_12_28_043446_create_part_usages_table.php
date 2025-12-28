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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_usages');
    }
};
