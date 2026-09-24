<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Money actually collected.
     *
     * There was no payment record of any kind: job_orders carried an
     * estimate and a final cost, the receipt PDF said "payment due upon
     * receipt", and nothing anywhere knew whether it ever was. A deposit at
     * intake and a balance on collection is the shop's normal pattern, so
     * this is a ledger of payments rather than a paid/unpaid flag.
     *
     * Rows are voided, never deleted: a receipt has been handed to a
     * customer by the time anyone notices a mistake, and the correction is
     * part of the record.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 10, 2);

            // cash | gcash | maya | bank_transfer | card | other
            $table->string('method');

            // GCash reference, bank transaction, card authorisation.
            $table->string('reference_no')->nullable();

            $table->string('receipt_number')->unique();

            // When the money changed hands, which is not always when it was
            // typed in — and it is what the income report groups on.
            $table->timestamp('paid_at');

            $table->foreignId('received_by')->constrained('users');
            $table->string('note')->nullable();

            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('paid_at');
            $table->index(['job_order_id', 'voided_at']);
        });

        // final_cost was declared, cast, and read by the receipt and the
        // dashboard, but never once written. Every finished job order
        // therefore has no invoice total at all, so the estimate the customer
        // agreed to becomes it.
        DB::table('job_orders')
            ->whereIn('status', ['completed', 'delivered'])
            ->where(function ($query) {
                $query->whereNull('final_cost')->orWhere('final_cost', 0);
            })
            ->whereNotNull('estimated_cost')
            ->update(['final_cost' => DB::raw('estimated_cost')]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
