<?php

namespace App\Models;

use App\Support\BoxQuantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    /**
     * The model does not use created_at / updated_at columns.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'purchase_order_id',
        'quotation_item_id',
        'preplist_item_id',
        'item_id',
        'qty',
        'box_size',
        'unit_price',
        'min_remaining_months',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'box_size' => 'integer',
            'unit_price' => 'decimal:2',
            'min_remaining_months' => 'integer',
        ];
    }

    /**
     * The purchase order this line belongs to.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * The quotation line this purchase order line was generated from.
     */
    public function quotationItem(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class);
    }

    /**
     * The demand line this order line satisfies (direct link for local purchases).
     */
    public function preplistItem(): BelongsTo
    {
        return $this->belongsTo(PreplistItem::class);
    }

    /**
     * The catalog item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * The allocations of this line against the prep list's project items.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PurchaseOrderItemAllocation::class);
    }

    /**
     * The recorded receipts against this line.
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceipt::class);
    }

    /**
     * The extended line amount.
     */
    public function extendedTotal(): float
    {
        return $this->qty * (float) $this->unit_price;
    }

    /**
     * Whether this line is tracked in base pieces. Local purchases of boxed
     * items store the requirement box size on the line so any pack bought can
     * be reconciled exactly: 10 boxes of 40 = 400 pieces, not 10 boxes.
     */
    public function trackedInPieces(): bool
    {
        return $this->box_size !== null && (int) $this->box_size > 0;
    }

    /**
     * The full ordered quantity in base pieces (or plain units).
     */
    public function orderedPieces(): int
    {
        return BoxQuantity::toPieces((int) $this->qty, $this->trackedInPieces() ? (int) $this->box_size : null);
    }

    /**
     * Units recorded as received/bought so far. For piece-tracked lines this
     * is the exact number of base pieces; otherwise it is the plain units.
     */
    public function receivedQty(): int
    {
        return (int) $this->receipts->sum('received_qty');
    }

    /**
     * Base pieces still outstanding on this line.
     */
    public function remainingPieces(): int
    {
        return max(0, $this->orderedPieces() - $this->receivedQty());
    }

    /**
     * Units still outstanding on this line, in the requirement unit (boxes for
     * boxed lines). A partly covered box rounds up — you cannot buy half a box.
     */
    public function remainingQty(): int
    {
        return $this->trackedInPieces()
            ? BoxQuantity::toBoxes($this->remainingPieces(), (int) $this->box_size)
            : $this->remainingPieces();
    }

    /**
     * Whether part of (but not all) the ordered quantity has been received.
     */
    public function isPartiallyReceived(): bool
    {
        return $this->receiptState() === 'partial';
    }

    /**
     * Quantity-driven receiving state: pending, partial or completed.
     */
    public function receiptState(): string
    {
        $received = $this->receivedQty();
        $ordered = $this->orderedPieces();

        return match (true) {
            $ordered > 0 && $received >= $ordered => 'completed',
            $received > 0 => 'partial',
            default => 'pending',
        };
    }

    /**
     * Whether the full ordered quantity has been received.
     */
    public function isReceived(): bool
    {
        return $this->receivedQty() >= $this->orderedPieces();
    }
}
