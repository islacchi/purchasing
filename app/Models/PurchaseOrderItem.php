<?php

namespace App\Models;

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
        'item_id',
        'qty',
        'unit_price',
        'min_remaining_months',
        'offered_shelf_life_months',
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
            'unit_price' => 'decimal:2',
            'min_remaining_months' => 'integer',
            'offered_shelf_life_months' => 'integer',
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
     * Whether the full ordered quantity has been received.
     */
    public function isReceived(): bool
    {
        return $this->receipts->sum('received_qty') >= $this->qty;
    }
}
