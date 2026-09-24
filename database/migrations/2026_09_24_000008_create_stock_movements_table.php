<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A ledger of physical stock changes, plus the reservation counter.
     *
     * Until now parts.in_stock only moved when an admin clicked a +/- button;
     * fitting a part to a repair changed nothing, so the low-stock alerts and
     * the inventory valuation were both detached from what the shop actually
     * used.
     *
     * in_stock keeps meaning "physically on the shelf", so every existing
     * read of it stays correct. reserved_stock is what is spoken for but not
     * yet fitted, and available = in_stock - reserved_stock.
     *
     * Reservations are deliberately NOT ledger entries: nothing physical
     * happens when one is taken, and keeping them out is what makes
     * SUM(stock_movements.quantity) == parts.in_stock a testable invariant.
     */
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->unsignedInteger('reserved_stock')->default(0)->after('in_stock');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained()->cascadeOnDelete();

            // Signed: positive receives and returns, negative consumption.
            $table->integer('quantity');

            // opening | receive | consume | return | adjust
            $table->string('type');

            // The job order line this movement belongs to, where there is one.
            $table->nullableMorphs('reference');

            // What the stock was worth as it moved. Read later from the
            // catalogue it would be whatever the price is today, which is not
            // what this movement cost.
            $table->decimal('unit_cost_price', 10, 2)->nullable();

            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['part_id', 'created_at']);
            $table->index('type');
        });

        // One opening entry per part, so the ledger balances to in_stock from
        // the very first day rather than only for movements recorded since.
        $parts = DB::table('parts')->select('id', 'in_stock', 'unit_cost_price')->get();

        foreach ($parts as $part) {
            if ((int) $part->in_stock === 0) {
                continue;
            }

            DB::table('stock_movements')->insert([
                'part_id' => $part->id,
                'quantity' => (int) $part->in_stock,
                'type' => 'opening',
                'unit_cost_price' => $part->unit_cost_price,
                'note' => 'Stock on hand when the ledger was introduced.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');

        Schema::table('parts', function (Blueprint $table) {
            $table->dropColumn('reserved_stock');
        });
    }
};
