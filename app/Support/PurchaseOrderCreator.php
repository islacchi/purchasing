<?php

namespace App\Support;

use App\Models\NumberSequence;
use App\Models\PreplistItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderItemAllocation;
use Illuminate\Support\Facades\DB;

/**
 * Creates a draft purchase order — header, lines, and project allocations —
 * from a set of awarded quotation lines.
 *
 * Quantities that each source project still needs are allocated
 * proportionally; the last positive-weight source absorbs any rounding,
 * matching what the per-quotation "Proceed to PO" flow produces.
 */
final class PurchaseOrderCreator
{
    /**
     * @param  array<int, array{quotation_item: \App\Models\QuotationItem, preplist_item: PreplistItem, qty: int, unit_price: float|string, min_remaining_months: int}>  $lines
     * @param  array{expected_delivery?: ?string, payment_terms?: ?string, deliver_to?: ?string, notes?: ?string}  $details
     */
    public static function createFor(int $createdBy, array $lines, array $details = []): PurchaseOrder
    {
        return DB::transaction(function () use ($createdBy, $lines, $details) {
            $quotation = $lines[0]['quotation_item']->quotation;

            $purchaseOrder = PurchaseOrder::create([
                'quotation_id' => $quotation->id,
                'supplier_id' => $quotation->supplier_id,
                'created_by' => $createdBy,
                'po_number' => self::nextPoNumber(),
                'status' => PurchaseOrder::STATUS_DRAFT,
                'expected_delivery_on' => $details['expected_delivery'] ?? null,
                'payment_terms' => $details['payment_terms'] ?? null,
                'deliver_to' => $details['deliver_to'] ?? null,
                'delivery_fee' => 0,
                'notes' => $details['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                /** @var PreplistItem $preplistItem */
                $preplistItem = $line['preplist_item'];

                $purchaseOrderItem = PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'quotation_item_id' => $line['quotation_item']->id,
                    'item_id' => $preplistItem->item_id,
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'min_remaining_months' => $line['min_remaining_months'],
                ]);

                self::allocateLine($purchaseOrderItem, $preplistItem, $line['qty']);
            }

            ActivityLogger::log('Created', 'Purchase Order', $purchaseOrder->po_number, $purchaseOrder);

            return $purchaseOrder;
        });
    }

    private static function allocateLine(PurchaseOrderItem $purchaseOrderItem, PreplistItem $preplistItem, int $qty): void
    {
        $sources = $preplistItem->sources->values();
        $shares = $sources->map(fn ($source) => $source->projectItem?->remainingQuantity() ?? 0);
        $totalRequested = (int) $shares->sum();
        $remaining = $qty;
        $lastPositiveIndex = $shares->filter(fn ($share) => $share > 0)->keys()->last();

        foreach ($sources as $index => $source) {
            $weight = $shares[$index];

            if ($totalRequested > 0) {
                $share = $lastPositiveIndex !== null && $index === $lastPositiveIndex
                    ? $remaining
                    : (int) floor($qty * $weight / $totalRequested);
            } else {
                $share = $index === 0 ? $qty : 0;
            }

            $remaining -= $share;

            if ($share <= 0) {
                continue;
            }

            PurchaseOrderItemAllocation::create([
                'purchase_order_item_id' => $purchaseOrderItem->id,
                'project_item_id' => $source->project_item_id,
                'qty' => $share,
            ]);
        }
    }

    private static function nextPoNumber(): string
    {
        $year = (int) now()->year;

        $sequence = NumberSequence::query()
            ->where('key', 'po')
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            $sequence = NumberSequence::query()->create([
                'key' => 'po',
                'year' => $year,
                'last_number' => 0,
            ]);
        }

        $sequence->last_number++;
        $sequence->save();

        return sprintf('PO-%d-%04d', $year, $sequence->last_number);
    }
}
