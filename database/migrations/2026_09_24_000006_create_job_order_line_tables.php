<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Job order line items, moved out of two JSON columns into real rows.
     *
     * job_orders.parts_needed and .issues held arrays, which meant "units sold
     * per part", "parts revenue by month" and "what is on backorder" were not
     * expressible in SQL — and three separate screens each re-hydrated the
     * arrays by hand to show a total.
     *
     * Prices and names are snapshots, deliberately. The customer is billed
     * what was agreed on the day, so a later price rise or a renamed service
     * must not rewrite a finished job order. The snapshot is also what makes
     * a historical income figure reproducible.
     */
    public function up(): void
    {
        Schema::create('job_order_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained()->cascadeOnDelete();

            // Nullable: a part can be retired from the catalogue long after
            // the repair that used it, and the line must survive that.
            $table->foreignId('part_id')->nullable()->constrained('parts')->nullOnDelete();

            $table->string('part_name');
            $table->string('sku')->nullable();
            $table->unsignedInteger('quantity')->default(1);

            // What the customer pays.
            $table->decimal('unit_sale_price', 10, 2)->default(0);
            // What the shop paid — needed for margin, and useless if read
            // later from the catalogue, which moves.
            $table->decimal('unit_cost_price', 10, 2)->default(0);

            // pending | reserved | backordered | consumed | released
            // Phase 3b drives these; until then every line stays 'pending',
            // which is exactly today's behaviour.
            $table->string('status')->default('pending');
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('consumed_at')->nullable();

            $table->timestamps();

            // "What is on backorder for this part", and the reservation
            // reconcile.
            $table->index(['part_id', 'status']);
        });

        Schema::create('job_order_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained()->cascadeOnDelete();

            // Previously joined by NAME: issues[].type matched services.name,
            // so renaming a service silently zeroed that job order's labour
            // on the portal, the receipt and the index modal.
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();

            $table->string('service_name');
            $table->decimal('labor_price', 10, 2)->default(0);
            $table->text('diagnosis')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_order_services');
        Schema::dropIfExists('job_order_parts');
    }
};
