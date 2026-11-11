<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItemAllocation extends Model
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
        'purchase_order_item_id',
        'project_item_id',
        'qty',
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
        ];
    }

    /**
     * The purchase order line this allocation belongs to.
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    /**
     * The project item this allocation covers.
     */
    public function projectItem(): BelongsTo
    {
        return $this->belongsTo(ProjectItem::class);
    }
}
