<?php

namespace App\Observers;

use App\Enums\StockMovementType;
use App\Models\Part;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;

/**
 * Gives a newly-added part its opening ledger entry.
 *
 * The migration wrote one for every part that already existed, so that
 * SUM(stock_movements.quantity) equals parts.in_stock. Without this, a part
 * added afterwards with stock already on the shelf would break that for good
 * — its units would exist without ever having been recorded as arriving.
 *
 * @see \App\Services\Inventory\InventoryService
 */
class PartObserver
{
    public function created(Part $part): void
    {
        if ($part->in_stock <= 0) {
            return;
        }

        StockMovement::create([
            'part_id' => $part->id,
            'quantity' => $part->in_stock,
            'type' => StockMovementType::OPENING,
            'unit_cost_price' => $part->unit_cost_price,
            'note' => 'Stock on hand when the part was added.',
            'user_id' => Auth::id(),
        ]);
    }
}
