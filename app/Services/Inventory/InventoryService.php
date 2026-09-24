<?php

namespace App\Services\Inventory;

use App\Enums\JobOrderStatus;
use App\Enums\StockMovementType;
use App\Models\JobOrder;
use App\Models\JobOrderEvent;
use App\Models\JobOrderPart;
use App\Models\Part;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The only thing in the application that changes stock.
 *
 * Stock moves in two phases. Approving a repair *reserves* the parts — they
 * stay on the shelf but are spoken for — and finishing the work *consumes*
 * them. A part that cannot be reserved because there is none left is marked
 * backordered instead of blocking the repair, which is what lets the shop
 * book in a phone it has no replacement part for yet.
 *
 * Every method locks the part rows it touches, because two counter staff
 * approving repairs for the last screen at the same moment would otherwise
 * both succeed.
 *
 * @see \App\Models\StockMovement
 */
class InventoryService
{
    /**
     * Reserve what this repair needs, and backorder what is not there.
     *
     * Called when a quote is approved — not at intake, because a quote that
     * is never approved should not hold inventory hostage.
     *
     * @return bool True when everything was reserved; false when at least one
     *              line is on backorder and the repair cannot start.
     */
    public function commit(JobOrder $jobOrder): bool
    {
        return DB::transaction(function () use ($jobOrder) {
            $lines = $jobOrder->parts()
                ->whereIn('status', [JobOrderPart::STATUS_PENDING, JobOrderPart::STATUS_BACKORDERED])
                ->get();

            $shortfalls = [];

            foreach ($lines as $line) {
                $part = $this->lockPart($line->part_id);

                if (! $part) {
                    // The catalogue entry is gone; there is nothing to hold.
                    $line->update(['status' => JobOrderPart::STATUS_RESERVED, 'reserved_at' => now()]);

                    continue;
                }

                if ($part->availableStock() >= $line->quantity) {
                    $part->increment('reserved_stock', $line->quantity);
                    $line->update([
                        'status' => JobOrderPart::STATUS_RESERVED,
                        'reserved_at' => now(),
                    ]);

                    continue;
                }

                $line->update(['status' => JobOrderPart::STATUS_BACKORDERED]);
                $shortfalls[] = $line;
            }

            if ($shortfalls === []) {
                return true;
            }

            $this->recordBackorder($jobOrder, $shortfalls);

            return false;
        });
    }

    /**
     * Take delivery of stock, then see whose backorders it clears.
     *
     * @return array<int, JobOrder> Job orders that are no longer waiting.
     */
    public function receive(Part $part, int $quantity, ?string $note = null, ?User $user = null): array
    {
        if ($quantity <= 0) {
            return [];
        }

        return DB::transaction(function () use ($part, $quantity, $note, $user) {
            $locked = $this->lockPart($part->id);
            $locked->addStock($quantity);

            $this->record($locked, $quantity, StockMovementType::RECEIVE, note: $note, user: $user);

            return $this->fulfilBackorders($locked);
        });
    }

    /**
     * Hand newly-arrived stock to whoever has been waiting longest.
     *
     * First come, first served by line age. A later, smaller order is not
     * allowed to jump ahead of one that has been waiting a week just because
     * it happens to fit.
     *
     * @return array<int, JobOrder>
     */
    public function fulfilBackorders(Part $part): array
    {
        $lines = JobOrderPart::backordered()
            ->where('part_id', $part->id)
            ->with('jobOrder')
            ->oldest()
            ->get();

        $released = [];

        foreach ($lines as $line) {
            $part->refresh();

            if ($part->availableStock() < $line->quantity) {
                continue;
            }

            $part->increment('reserved_stock', $line->quantity);
            $line->update([
                'status' => JobOrderPart::STATUS_RESERVED,
                'reserved_at' => now(),
                'fulfilled_at' => now(),
            ]);

            $jobOrder = $line->jobOrder;

            if ($jobOrder && ! $jobOrder->parts()->backordered()->exists()) {
                $released[] = $jobOrder;
            }
        }

        return $released;
    }

    /**
     * Take the reserved parts off the shelf.
     *
     * Called when the work is marked done rather than when it starts: that is
     * the first moment the part is definitively in the customer's device, and
     * an abandoned in-progress repair would otherwise eat the count for good.
     */
    public function consume(JobOrder $jobOrder): void
    {
        DB::transaction(function () use ($jobOrder) {
            foreach ($jobOrder->parts()->reserved()->get() as $line) {
                $part = $this->lockPart($line->part_id);

                if ($part) {
                    $part->deductStock($line->quantity);
                    $part->decrement('reserved_stock', min($line->quantity, $part->reserved_stock));

                    $this->record(
                        $part,
                        -$line->quantity,
                        StockMovementType::CONSUME,
                        reference: $line,
                        // The cost agreed when the line was added, not today's.
                        unitCost: (float) $line->unit_cost_price,
                    );
                }

                $line->update([
                    'status' => JobOrderPart::STATUS_CONSUMED,
                    'consumed_at' => now(),
                ]);
            }
        });
    }

    /**
     * Give back whatever this repair was holding.
     *
     * A reserved part simply stops being spoken for. A consumed one has to
     * physically come back, which is a real movement.
     */
    public function release(JobOrder $jobOrder): void
    {
        DB::transaction(function () use ($jobOrder) {
            // Queried, not read off $jobOrder->parts: the caller loaded that
            // relation before commit() or consume() moved the lines on, and a
            // stale status here means stock is quietly never given back.
            foreach ($jobOrder->parts()->get() as $line) {
                $this->releaseLine($line);
            }
        });
    }

    /** Release one line — used when a part is removed from a repair. */
    public function releaseLine(JobOrderPart $line): void
    {
        $part = $this->lockPart($line->part_id);

        if ($part) {
            if ($line->status === JobOrderPart::STATUS_RESERVED) {
                $part->decrement('reserved_stock', min($line->quantity, $part->reserved_stock));
            }

            if ($line->status === JobOrderPart::STATUS_CONSUMED) {
                $part->addStock($line->quantity);

                $this->record(
                    $part,
                    $line->quantity,
                    StockMovementType::RETURN_TO_STOCK,
                    reference: $line,
                    unitCost: (float) $line->unit_cost_price,
                );
            }
        }

        $line->update(['status' => JobOrderPart::STATUS_RELEASED]);
    }

    /**
     * A manual correction from the inventory screen.
     *
     * Separate from receive() because a stocktake is not a delivery and
     * should not start handing parts to backorders as though one had arrived.
     */
    public function adjust(Part $part, int $delta, ?string $note = null, ?User $user = null): array
    {
        if ($delta === 0) {
            return [];
        }

        return DB::transaction(function () use ($part, $delta, $note, $user) {
            $locked = $this->lockPart($part->id);

            if ($delta > 0) {
                $locked->addStock($delta);
            } elseif (! $locked->deductStock(abs($delta))) {
                // Refuse to drive the shelf negative.
                return [];
            }

            $this->record($locked, $delta, StockMovementType::ADJUST, note: $note, user: $user);

            return $delta > 0 ? $this->fulfilBackorders($locked) : [];
        });
    }

    /**
     * Parts the shop needs to order, with how many are outstanding.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function shortfalls()
    {
        return JobOrderPart::backordered()
            ->selectRaw('part_id, SUM(quantity) as needed, COUNT(*) as job_order_count, MIN(created_at) as waiting_since')
            ->whereNotNull('part_id')
            ->groupBy('part_id')
            ->with('part')
            ->orderBy('waiting_since')
            ->get();
    }

    private function lockPart(?int $partId): ?Part
    {
        if (! $partId) {
            return null;
        }

        return Part::whereKey($partId)->lockForUpdate()->first();
    }

    private function record(
        Part $part,
        int $quantity,
        StockMovementType $type,
        ?JobOrderPart $reference = null,
        ?float $unitCost = null,
        ?string $note = null,
        ?User $user = null,
    ): void {
        StockMovement::create([
            'part_id' => $part->id,
            'quantity' => $quantity,
            'type' => $type,
            'reference_type' => $reference ? $reference->getMorphClass() : null,
            'reference_id' => $reference?->id,
            'unit_cost_price' => $unitCost ?? (float) $part->unit_cost_price,
            'note' => $note,
            'user_id' => $user?->id ?? Auth::id(),
        ]);
    }

    /**
     * @param  array<int, JobOrderPart>  $shortfalls
     */
    private function recordBackorder(JobOrder $jobOrder, array $shortfalls): void
    {
        $names = array_map(fn (JobOrderPart $line) => $line->part_name, $shortfalls);

        JobOrderEvent::create([
            'job_order_id' => $jobOrder->id,
            'type' => JobOrderEvent::TYPE_PART_BACKORDERED,
            'description' => count($names) === 1
                ? "Waiting on a replacement part: {$names[0]}."
                : 'Waiting on replacement parts: '.implode(', ', $names).'.',
            'meta' => ['parts' => $names],
            // The customer should know why their repair has not started.
            'is_customer_visible' => true,
            'user_id' => Auth::id(),
        ]);
    }

    /** The status a job order should sit at once its parts are committed. */
    public function statusAfterCommit(bool $fullyReserved): JobOrderStatus
    {
        return $fullyReserved ? JobOrderStatus::APPROVED : JobOrderStatus::AWAITING_PARTS;
    }
}
