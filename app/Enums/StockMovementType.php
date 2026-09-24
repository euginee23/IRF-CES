<?php

namespace App\Enums;

/**
 * Why a part's physical stock changed.
 *
 * Reservations are absent on purpose: holding a part for an approved repair
 * moves nothing off the shelf, so it is not a movement. That is what keeps
 * the ledger's sum equal to parts.in_stock.
 */
enum StockMovementType: string
{
    /** Stock on hand when the ledger was introduced. */
    case OPENING = 'opening';

    /** A delivery arrived, or an admin added stock by hand. */
    case RECEIVE = 'receive';

    /** Fitted to a device and gone. */
    case CONSUME = 'consume';

    /** Came back: a cancelled repair, or a part removed after being fitted. */
    case RETURN_TO_STOCK = 'return';

    /** A stocktake correction. */
    case ADJUST = 'adjust';

    public function label(): string
    {
        return match ($this) {
            self::OPENING => 'Opening balance',
            self::RECEIVE => 'Received',
            self::CONSUME => 'Used on a repair',
            self::RETURN_TO_STOCK => 'Returned to stock',
            self::ADJUST => 'Adjustment',
        };
    }
}
