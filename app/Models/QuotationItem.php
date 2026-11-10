<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
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
        'quotation_id',
        'preplist_item_id',
        'min_remaining_months',
        'unit_price',
        'offered_shelf_life_months',
        'available_qty',
        'quoted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_remaining_months' => 'integer',
            'unit_price' => 'decimal:2',
            'offered_shelf_life_months' => 'integer',
            'available_qty' => 'integer',
            'quoted_at' => 'datetime',
        ];
    }

    /**
     * The quotation this line item belongs to.
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * The prep list item this quotation line prices.
     */
    public function preplistItem(): BelongsTo
    {
        return $this->belongsTo(PreplistItem::class);
    }
}
