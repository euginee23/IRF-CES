<?php

namespace App\Console\Commands;

use App\Models\JobOrderPart;
use App\Models\Part;
use App\Models\StockMovement;
use Illuminate\Console\Command;

/**
 * Checks the two invariants the stock system rests on.
 *
 * Both are maintained by InventoryService, but they are worth being able to
 * verify from outside it: a drift here means the low-stock alerts, the
 * inventory valuation and the backorder queue are all quietly wrong, and
 * nothing else in the application would say so.
 *
 * @see \App\Services\Inventory\InventoryService
 */
class ReconcileInventory extends Command
{
    protected $signature = 'inventory:reconcile {--fix : Correct reserved_stock to match the reserved lines}';

    protected $description = 'Verify that the stock ledger and the reservation counters agree with the parts table';

    public function handle(): int
    {
        $ledgerProblems = $this->checkLedger();
        $reservationProblems = $this->checkReservations();

        if ($ledgerProblems === 0 && $reservationProblems === 0) {
            $this->info('Inventory reconciles: the ledger and the reservations both agree with parts.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error("{$ledgerProblems} ledger mismatch(es), {$reservationProblems} reservation mismatch(es).");

        return self::FAILURE;
    }

    /** SUM(stock_movements.quantity) must equal parts.in_stock. */
    private function checkLedger(): int
    {
        $sums = StockMovement::selectRaw('part_id, SUM(quantity) as total')
            ->groupBy('part_id')
            ->pluck('total', 'part_id');

        $problems = 0;

        foreach (Part::cursor() as $part) {
            $ledger = (int) ($sums[$part->id] ?? 0);

            if ($ledger === $part->in_stock) {
                continue;
            }

            $problems++;
            $this->warn("Ledger: {$part->name} ({$part->sku}) — ledger says {$ledger}, parts.in_stock says {$part->in_stock}.");
        }

        return $problems;
    }

    /** parts.reserved_stock must equal the quantity on reserved lines. */
    private function checkReservations(): int
    {
        $reserved = JobOrderPart::reserved()
            ->selectRaw('part_id, SUM(quantity) as total')
            ->whereNotNull('part_id')
            ->groupBy('part_id')
            ->pluck('total', 'part_id');

        $problems = 0;

        foreach (Part::cursor() as $part) {
            $expected = (int) ($reserved[$part->id] ?? 0);

            if ($expected === $part->reserved_stock) {
                continue;
            }

            $problems++;
            $this->warn("Reservations: {$part->name} ({$part->sku}) — lines reserve {$expected}, parts.reserved_stock says {$part->reserved_stock}.");

            if ($this->option('fix')) {
                $part->update(['reserved_stock' => $expected]);
                $this->line("  fixed to {$expected}.");
            }
        }

        return $this->option('fix') ? 0 : $problems;
    }
}
