<?php

namespace App\Models;

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
     * Quantity still to procure after deducting what the office already has.
     */
    public function remainingQuantity(): int
    {
        return max(0, $this->quantity - (int) $this->in_stock);
    }

    /**
     * Whether on-hand stock alone fully covers the awarded quantity.
     */
    public function isFulfilledByStock(): bool
    {
        return $this->quantity > 0 && (int) $this->in_stock >= $this->quantity;
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

        if ((int) $this->quantity > 0 && (int) $this->in_stock >= (int) $this->quantity) {
            return 'fulfilled';
        }

        return (int) $this->in_stock > 0 ? 'partial' : 'pending';
    }

    /**
     * Whether only part of the awarded quantity has arrived.
     */
    // public function isPartiallyFulfilled(): bool
    // {
    //     return $this->quantityStatus() === 'partial';
    // }
    public function isPartiallyFulfilled(): bool
{
    return ! $this->isFulfilled() && (int) $this->in_stock > 0;
}
public function coveredQuantity(): int
{
    if ($this->fulfillment_status === 'fulfilled') {
        return (int) $this->quantity;
    }

    return min((int) $this->quantity, (int) $this->in_stock);
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
