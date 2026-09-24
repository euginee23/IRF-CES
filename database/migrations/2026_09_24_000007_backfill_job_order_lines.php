<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Copy the JSON line items onto the new tables.
     *
     * Query builder throughout, never the Eloquent models: JobOrder's casts,
     * fillable and boot() all change around this migration, and one bound to
     * them breaks the next time someone runs a fresh migrate.
     *
     * The source columns are left in place. They are the only copy of this
     * data, so they stay for one release as a frozen audit trail and are
     * dropped separately once the new read paths have been used in anger.
     */
    public function up(): void
    {
        DB::table('job_orders')
            ->select('id', 'parts_needed', 'issues')
            ->orderBy('id')
            ->chunkById(200, function ($jobOrders) {
                foreach ($jobOrders as $jobOrder) {
                    $this->migrateParts($jobOrder);
                    $this->migrateServices($jobOrder);
                }
            });
    }

    private function migrateParts(object $jobOrder): void
    {
        // Re-running must not double the lines.
        if (DB::table('job_order_parts')->where('job_order_id', $jobOrder->id)->exists()) {
            return;
        }

        foreach ($this->decode($jobOrder->parts_needed) as $line) {
            $partId = isset($line['part_id']) ? (int) $line['part_id'] : null;

            // The catalogue is the only place the cost price ever lived, so
            // it is read once here. A part since deleted leaves cost 0, which
            // shows as 100% margin rather than inventing a number.
            $part = $partId
                ? DB::table('parts')->where('id', $partId)->first(['name', 'sku', 'unit_cost_price', 'unit_sale_price'])
                : null;

            DB::table('job_order_parts')->insert([
                'job_order_id' => $jobOrder->id,
                'part_id' => $part ? $partId : null,
                'part_name' => $line['part_name'] ?? $part->name ?? 'Unknown part',
                'sku' => $part->sku ?? null,
                'quantity' => max(1, (int) ($line['quantity'] ?? 1)),
                // The snapshot on the job order wins: it is what the customer
                // was quoted, even where the catalogue has moved since.
                'unit_sale_price' => (float) ($line['unit_sale_price'] ?? $part->unit_sale_price ?? 0),
                'unit_cost_price' => (float) ($part->unit_cost_price ?? 0),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function migrateServices(object $jobOrder): void
    {
        if (DB::table('job_order_services')->where('job_order_id', $jobOrder->id)->exists()) {
            return;
        }

        foreach ($this->decode($jobOrder->issues) as $line) {
            $name = trim((string) ($line['type'] ?? ''));

            if ($name === '') {
                continue;
            }

            // Matched by name because that is all the JSON ever held. A
            // service since renamed finds nothing and lands at price 0 —
            // which is what these job orders already displayed, so nothing
            // regresses; the difference is that it is now visible and
            // correctable instead of being recomputed wrongly every render.
            $service = DB::table('services')->where('name', $name)->first(['id', 'labor_price']);

            DB::table('job_order_services')->insert([
                'job_order_id' => $jobOrder->id,
                'service_id' => $service->id ?? null,
                'service_name' => $name,
                'labor_price' => (float) ($service->labor_price ?? 0),
                'diagnosis' => $line['diagnosis'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function decode(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? array_filter($decoded, 'is_array') : [];
    }

    public function down(): void
    {
        DB::table('job_order_services')->delete();
        DB::table('job_order_parts')->delete();
    }
};
