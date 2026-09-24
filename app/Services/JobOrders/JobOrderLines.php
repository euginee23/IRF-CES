<?php

namespace App\Services\JobOrders;

use App\Models\JobOrder;
use App\Models\JobOrderPart;
use App\Models\Part;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

/**
 * Writes a job order's parts and services from the intake form.
 *
 * Both the create and edit screens built the same JSON blobs inline, each
 * with its own copy of the "look the part up and denormalise its name and
 * price" logic. This is that logic, once, writing rows instead.
 *
 * Prices are snapshotted here rather than read back at display time, because
 * the customer is billed what was agreed on the day.
 */
class JobOrderLines
{
    /**
     * Replace this job order's lines with what the form holds.
     *
     * @param  array<int, array{type?: string, diagnosis?: string|null}>  $services
     * @param  array<int, array{part_id?: int|string, quantity?: int|string}>  $parts
     */
    public function sync(JobOrder $jobOrder, array $services, array $parts): void
    {
        DB::transaction(function () use ($jobOrder, $services, $parts) {
            $this->syncServices($jobOrder, $services);
            $this->syncParts($jobOrder, $parts);
        });

        $jobOrder->load(['parts', 'services']);
    }

    /**
     * Services are a straight replace: they carry no state of their own, so
     * there is nothing to preserve across an edit.
     *
     * @param  array<int, array<string, mixed>>  $services
     */
    private function syncServices(JobOrder $jobOrder, array $services): void
    {
        $jobOrder->services()->delete();

        $catalogue = Service::whereIn('name', array_filter(array_column($services, 'type')))
            ->get()
            ->keyBy('name');

        foreach ($services as $line) {
            $name = trim((string) ($line['type'] ?? ''));

            if ($name === '') {
                continue;
            }

            $service = $catalogue->get($name);

            $jobOrder->services()->create([
                'service_id' => $service?->id,
                'service_name' => $name,
                'labor_price' => (float) ($service->labor_price ?? 0),
                'diagnosis' => $line['diagnosis'] ?? null,
            ]);
        }
    }

    /**
     * Parts are matched to the lines already there rather than replaced.
     *
     * A part line carries stock state — reserved, backordered, consumed — so
     * deleting and recreating it on every save would drop a reservation the
     * shop is relying on. Editing the quantity of a part that is already on
     * the order keeps its row, and therefore its hold.
     *
     * @param  array<int, array<string, mixed>>  $parts
     */
    private function syncParts(JobOrder $jobOrder, array $parts): void
    {
        $existing = $jobOrder->parts()->get()->keyBy('part_id');
        $catalogue = Part::whereIn('id', array_filter(array_column($parts, 'part_id')))
            ->get()
            ->keyBy('id');

        $keptPartIds = [];

        foreach ($parts as $line) {
            $partId = isset($line['part_id']) ? (int) $line['part_id'] : null;

            if (! $partId) {
                continue;
            }

            $quantity = max(1, (int) ($line['quantity'] ?? 1));
            $keptPartIds[] = $partId;

            if ($current = $existing->get($partId)) {
                // Only the quantity is editable; the agreed prices stay put.
                $current->update(['quantity' => $quantity]);

                continue;
            }

            $part = $catalogue->get($partId);

            $jobOrder->parts()->create([
                'part_id' => $partId,
                'part_name' => $part->name ?? 'Unknown part',
                'sku' => $part->sku ?? null,
                'quantity' => $quantity,
                'unit_sale_price' => (float) ($part->unit_sale_price ?? 0),
                'unit_cost_price' => (float) ($part->unit_cost_price ?? 0),
                'status' => JobOrderPart::STATUS_PENDING,
            ]);
        }

        // Lines the form no longer holds. Phase 3b hands back any stock these
        // were holding before they go.
        $jobOrder->parts()
            ->when($keptPartIds !== [], fn ($q) => $q->whereNotIn('part_id', $keptPartIds))
            ->delete();
    }

    /**
     * What the form's current selection would cost, before anything is saved.
     *
     * Used for the live estimate on the intake screens, which cannot read
     * totals off a job order that does not exist yet.
     *
     * @param  array<int, array<string, mixed>>  $services
     * @param  array<int, array<string, mixed>>  $parts
     */
    public function previewTotal(array $services, array $parts): float
    {
        // Summed per chosen line, not per distinct service: booking the same
        // service twice is charged twice, which is what the intake screens
        // have always shown.
        $priceList = Service::whereIn('name', array_filter(array_column($services, 'type')))
            ->pluck('labor_price', 'name');

        $laborTotal = 0.0;

        foreach ($services as $line) {
            $laborTotal += (float) ($priceList[$line['type'] ?? ''] ?? 0);
        }

        $catalogue = Part::whereIn('id', array_filter(array_column($parts, 'part_id')))
            ->get()
            ->keyBy('id');

        $partsTotal = 0.0;

        foreach ($parts as $line) {
            $part = $catalogue->get((int) ($line['part_id'] ?? 0));

            if (! $part) {
                continue;
            }

            $partsTotal += (float) $part->unit_sale_price * max(1, (int) ($line['quantity'] ?? 1));
        }

        return round((float) $laborTotal + $partsTotal, 2);
    }
}
