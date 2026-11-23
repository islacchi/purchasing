<?php

namespace App\Support;

use App\Models\NumberSequence;
use App\Models\PreplistItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderItemAllocation;
use App\Models\PurchaseOrderReceipt;
use Illuminate\Support\Facades\DB;

/**
 * Creates a draft purchase order — header, lines, and project allocations —
 * from a set of awarded quotation lines or from lines raised directly
 * against a local store (no quotation at all).
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

            $purchaseOrder = self::createDraftHeader(
                $createdBy,
                $quotation->id,
                (int) $quotation->supplier_id,
                PurchaseOrder::SOURCE_QUOTATION,
                $details
            );

            foreach ($lines as $line) {
                /** @var PreplistItem $preplistItem */
                $preplistItem = $line['preplist_item'];

                self::writeLine($purchaseOrder, $line['quotation_item']->id, $preplistItem, $line);
            }

            ActivityLogger::log('Created', 'Purchase Order', $purchaseOrder->po_number, $purchaseOrder);

            return $purchaseOrder;
        });
    }

    /**
     * Raise a draft purchase order against a local store with no quotation at
     * all. Allocations and numbering behave exactly like the awarded-quotation
     * path.
     *
     * @param  array<int, array{preplist_item: PreplistItem, qty: int, unit_price: float|string|null, min_remaining_months?: ?int}>  $lines
     * @param  array{supplier_id: int|string, expected_delivery?: ?string, payment_terms?: ?string, deliver_to?: ?string, notes?: ?string}  $details
     */
    public static function createForLocalPurchase(int $createdBy, array $lines, array $details): PurchaseOrder
    {
        if ($lines === []) {
            throw new \InvalidArgumentException('Select at least one line to raise a local purchase order.');
        }

        $supplierId = (int) ($details['supplier_id'] ?? 0);

        if ($supplierId <= 0) {
            throw new \InvalidArgumentException('A supplier is required to raise a local purchase order.');
        }

        $anyBought = (bool) collect($lines)->contains(fn (array $line) => ! empty($line['bought_now']));
        $allBought = $anyBought && ! collect($lines)->contains(fn (array $line) => empty($line['bought_now']));

        $status = $allBought ? PurchaseOrder::STATUS_COMPLETED : ($anyBought ? PurchaseOrder::STATUS_PARTIALLY_RECEIVED : PurchaseOrder::STATUS_DRAFT);
        $issuedOn = $anyBought ? today()->toDateString() : null;

        return DB::transaction(function () use ($createdBy, $lines, $details, $supplierId, $status, $issuedOn, $anyBought) {
            $purchaseOrder = self::createDraftHeader(
                $createdBy,
                null,
                $supplierId,
                PurchaseOrder::SOURCE_LOCAL,
                $details,
                $status,
                $issuedOn
            );

            foreach ($lines as $line) {
                /** @var PreplistItem $preplistItem */
                $preplistItem = $line['preplist_item'];

                $poItem = self::writeLine($purchaseOrder, null, $preplistItem, array_merge([
                    'min_remaining_months' => $preplistItem->min_shelf_life_months,
                    // Boxed local lines snapshot the requirement pack so any
                    // pack bought later is reconciled in base pieces.
                    'box_size' => $preplistItem->item?->box_size,
                ], $line));

                if (! empty($line['bought_now'])) {
                    PurchaseReceipts::finalize($poItem, $createdBy, (int) $line['qty']);
                }
            }

            ActivityLogger::log('Created', 'Purchase Order', $purchaseOrder->po_number.($anyBought ? ' (local purchase, partially bought)' : ' (local purchase)'), $purchaseOrder);

            return $purchaseOrder;
        });
    }

    private static function createDraftHeader(int $createdBy, ?int $quotationId, int $supplierId, string $source, array $details, ?string $status = null, ?string $issuedOn = null): PurchaseOrder
    {
        return PurchaseOrder::create([
            'quotation_id' => $quotationId,
            'supplier_id' => $supplierId,
            'created_by' => $createdBy,
            'po_number' => self::nextPoNumber(),
            'status' => $status ?? PurchaseOrder::STATUS_DRAFT,
            'source' => $source,
            'issued_on' => $issuedOn,
            'expected_delivery_on' => $details['expected_delivery'] ?? null,
            'payment_terms' => $details['payment_terms'] ?? null,
            'deliver_to' => $details['deliver_to'] ?? null,
            'delivery_fee' => 0,
            'notes' => $details['notes'] ?? null,
        ]);
    }

    private static function writeLine(PurchaseOrder $purchaseOrder, ?int $quotationItemId, PreplistItem $preplistItem, array $line): PurchaseOrderItem
    {
        $purchaseOrderItem = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'quotation_item_id' => $quotationItemId,
            'preplist_item_id' => $preplistItem->id,
            'item_id' => $preplistItem->item_id,
            'qty' => $line['qty'],
            'box_size' => $line['box_size'] ?? null,
            'unit_price' => $line['unit_price'],
            'min_remaining_months' => $line['min_remaining_months'] ?? null,
        ]);

        self::allocateLine($purchaseOrderItem, $preplistItem, (int) $line['qty']);

        return $purchaseOrderItem;
    }

    public static function allocateLine(PurchaseOrderItem $purchaseOrderItem, PreplistItem $preplistItem, int $qty): void
    {
        // Piece-tracked (boxed local) lines allocate their base pieces so the
        // stock bump never loses a partial box; plain lines allocate units.
        $boxSize = $purchaseOrderItem->trackedInPieces() ? (int) $purchaseOrderItem->box_size : null;
        $totalQuantity = BoxQuantity::toPieces($qty, $boxSize);

        $sources = $preplistItem->sources->values();
        $shares = $sources->map(fn ($source) => $boxSize !== null
            ? ($source->projectItem?->remainingPieces() ?? 0)
            : ($source->projectItem?->remainingQuantity() ?? 0));
        $totalRequested = (int) $shares->sum();
        $remaining = $totalQuantity;
        $lastPositiveIndex = $shares->filter(fn ($share) => $share > 0)->keys()->last();

        foreach ($sources as $index => $source) {
            $weight = $shares[$index];

            if ($totalRequested > 0) {
                $share = $lastPositiveIndex !== null && $index === $lastPositiveIndex
                    ? $remaining
                    : (int) floor($totalQuantity * $weight / $totalRequested);
            } else {
                $share = $index === 0 ? $totalQuantity : 0;
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
