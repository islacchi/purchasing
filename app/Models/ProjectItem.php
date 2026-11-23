<?php

namespace App\Models;

use App\Support\BoxQuantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectItem extends Model
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
        'project_id',
        'item_id',
        'quantity',
        'in_stock',
        'unit_cost',
        'awarded_unit_price',
        'fulfillment_status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'in_stock' => 'integer',
            'unit_cost' => 'decimal:2',
            'awarded_unit_price' => 'decimal:2',
        ];
    }

    /**
     * The box-size requirement of the catalog item, when it has one.
     */
    public function boxSize(): ?int
    {
        $boxSize = $this->item?->box_size;

        return $boxSize !== null && (int) $boxSize > 0 ? (int) $boxSize : null;
    }

    /**
     * Whether the item is packaged to a box quantity.
     */
    public function hasBoxRequirement(): bool
    {
        return $this->boxSize() !== null;
    }

    /**
     * The awarded requirement in base pieces. Items with a box size are
     * entered in boxes (100 boxes of 20 = 2,000 pieces); unpackaged items keep
     * their plain unit quantity.
     */
    public function requiredPieces(): int
    {
        return BoxQuantity::toPieces((int) $this->quantity, $this->boxSize());
    }

    /**
     * On-hand stock in base pieces. Receipts from any pack size are stored in
     * pieces so partial boxes are never lost to rounding.
     */
    public function inStockPieces(): int
    {
        return (int) $this->in_stock;
    }

    /**
     * Base pieces still to procure after deducting stock on hand.
     */
    public function remainingPieces(): int
    {
        return max(0, $this->requiredPieces() - $this->inStockPieces());
    }

    /**
     * Quantity still to procure, expressed in the requirement unit (boxes for
     * boxed items). A partly covered box rounds up because a full box is the
     * smallest thing that can be bought.
     */
    public function remainingQuantity(): int
    {
        return BoxQuantity::toBoxes($this->remainingPieces(), $this->boxSize());
    }

    /**
     * Whether on-hand stock alone fully covers the awarded quantity.
     */
    public function isFulfilledByStock(): bool
    {
        return $this->requiredPieces() > 0 && $this->inStockPieces() >= $this->requiredPieces();
    }

    /**
     * Quantity-driven fulfillment state: pending until some stock arrives,
     * partial while in-stock is short of the awarded quantity, fulfilled once
     * covered. A stored "waived" flag still wins.
     */
    public function quantityStatus(): string
    {
        if ($this->fulfillment_status === 'waived') {
            return 'waived';
        }

        if ($this->isFulfilledByStock()) {
            return 'fulfilled';
        }

        return $this->inStockPieces() > 0 ? 'partial' : 'pending';
    }

    /**
     * Whether only part of the awarded quantity has arrived.
     */
    public function isPartiallyFulfilled(): bool
    {
        return ! $this->isFulfilled() && $this->inStockPieces() > 0;
    }

    /**
     * Base pieces already covered by stock (capped at the requirement).
     */
    public function coveredQuantity(): int
    {
        if ($this->fulfillment_status === 'fulfilled') {
            return $this->requiredPieces();
        }

        return min($this->requiredPieces(), $this->inStockPieces());
    }

    /**
     * Whether the line counts as fulfilled (received via PO, or covered by stock).
     */
    public function isFulfilled(): bool
    {
        return $this->quantityStatus() === 'fulfilled';
    }

    /**
     * The project this item belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The catalog item referenced by this project item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
    
}
