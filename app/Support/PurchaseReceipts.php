<?php

namespace App\Support;

use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderReceipt;
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
     * @param  int  $qty  How many units arrived (or were bought) this time.
     * @param  bool  $allowOverage  When true, a bought quantity may exceed the
     *                              ordered quantity (box-driven purchases can cover
     *                              a full box beyond the outstanding need).
     */
    public static function finalize(PurchaseOrderItem $line, int $userId, int $qty, bool $allowOverage = false): void
    {
        $remaining = $line->remainingQty();

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
     */
    public static function bumpStock(PurchaseOrderItem $line, int $qty): void
    {
        $line->loadMissing('allocations.projectItem');

        $allocations = $line->allocations->values();
        $orderedQty = (int) $line->qty;

        if ($allocations->isEmpty() || $orderedQty <= 0 || $qty <= 0) {
            return;
        }

        $newTotal = min($orderedQty, (int) $line->receipts()->sum('received_qty'));
        $oldTotal = max(0, $newTotal - $qty);

        $before = self::split($allocations, $orderedQty, $oldTotal);
        $after = self::split($allocations, $orderedQty, $newTotal);

        foreach ($allocations as $index => $allocation) {
            $projectItem = $allocation->projectItem;
            $delta = ($after[$index] ?? 0) - ($before[$index] ?? 0);

            if (! $projectItem || $delta <= 0) {
                continue;
            }

            $newStock = (int) $projectItem->in_stock + $delta;

            $newStatus = match (true) {
                (int) $projectItem->quantity > 0 && $newStock >= (int) $projectItem->quantity => 'fulfilled',
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
