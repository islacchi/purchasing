<?php

namespace App\Support;

use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderReceipt;
use App\Support\BoxQuantity;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Records a receipt against a PO line. Receipts accumulate: a line can be
 * received/bought over several trips until its ordered quantity is covered.
 * On-hand stock is bumped by the share of the received units that belongs to
 * each project allocation, and lines become fulfilled once fully covered.
 */
final class PurchaseReceipts
{
    /**
     * @param  int  $qty  How many base pieces arrived (or were bought) this
     *                    time for piece-tracked lines; plain units otherwise.
     * @param  bool  $allowOverage  When true, a bought quantity may exceed the
     *                              ordered quantity (box-driven purchases can cover
     *                              a full box beyond the outstanding need).
     */
    public static function finalize(PurchaseOrderItem $line, int $userId, int $qty, bool $allowOverage = false): void
    {
        $remaining = $line->trackedInPieces() ? $line->remainingPieces() : $line->remainingQty();

        if ($qty < 1) {
            throw new InvalidArgumentException('Receipt quantity must be at least 1 for this line.');
        }

        if (! $allowOverage && $qty > $remaining) {
            throw new InvalidArgumentException(
                'Receipt quantity must be between 1 and '.$remaining.' for this line.'
            );
        }

        DB::transaction(function () use ($line, $userId, $qty) {
            PurchaseOrderReceipt::create([
                'purchase_order_item_id' => $line->id,
                'received_by' => $userId,
                'received_qty' => $qty,
                'received_on' => today(),
            ]);

            self::bumpStock($line, $qty);
        });
    }

    /**
     * Add this receipt's share of each allocation to the matching project
     * lines' on-hand stock, and mark those lines fulfilled once fully covered.
     *
     * Piece-tracked lines (local purchases of boxed items) already carry their
     * quantities in base pieces, so the stock bump is a straight split. Older
     * / quoted lines carry line units; for boxed project items one line unit
     * stands for one requirement box and is converted to pieces.
     */
    public static function bumpStock(PurchaseOrderItem $line, int $qty): void
    {
        $line->loadMissing('allocations.projectItem.item');

        $allocations = $line->allocations->values();

        if ($allocations->isEmpty() || $qty <= 0) {
            return;
        }

        $tracked = $line->trackedInPieces();
        $ordered = $tracked ? $line->orderedPieces() : (int) $line->qty;

        if ($ordered <= 0) {
            return;
        }

        $newTotal = min($ordered, (int) $line->receipts()->sum('received_qty'));
        $oldTotal = max(0, $newTotal - $qty);

        $denominator = (int) $allocations->sum('qty');
        $denominator = $denominator > 0 ? $denominator : $ordered;

        $before = self::split($allocations, $denominator, $oldTotal);
        $after = self::split($allocations, $denominator, $newTotal);

        foreach ($allocations as $index => $allocation) {
            $projectItem = $allocation->projectItem;
            $delta = ($after[$index] ?? 0) - ($before[$index] ?? 0);

            if (! $projectItem || $delta <= 0) {
                continue;
            }

            // Tracked lines count in pieces already; legacy lines count in
            // requirement boxes and must be converted for boxed items.
            $deltaPieces = $tracked
                ? $delta
                : BoxQuantity::toPieces($delta, $projectItem->boxSize());

            $newStock = $projectItem->inStockPieces() + $deltaPieces;

            $newStatus = match (true) {
                $projectItem->requiredPieces() > 0 && $newStock >= $projectItem->requiredPieces() => 'fulfilled',
                $newStock > 0 => 'partial',
                default => $projectItem->fulfillment_status,
            };

            $projectItem->update([
                'in_stock' => $newStock,
                'fulfillment_status' => $newStatus,
            ]);
        }
    }

    /**
     * Split a cumulative received total across the line's allocations,
     * proportionally to each allocation's share of the ordered quantity.
     * The last allocation absorbs the rounding remainder.
     *
     * @return array<int, int>
     */
    private static function split($allocations, int $orderedQty, int $receivedTotal): array
    {
        $shares = [];
        $assigned = 0;
        $lastIndex = $allocations->count() - 1;

        foreach ($allocations as $index => $allocation) {
            $allocQty = (int) $allocation->qty;

            $share = $index === $lastIndex
                ? $receivedTotal - $assigned
                : (int) floor($receivedTotal * $allocQty / $orderedQty);

            $share = max(0, min($allocQty, $share));
            $shares[$index] = $share;
            $assigned += $share;
        }

        return $shares;
    }
}
