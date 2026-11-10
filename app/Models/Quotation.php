<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
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
        'preplist_id',
        'supplier_id',
        'created_by',
        'rfq_number',
        'generated_at',
        'sent_at',
        'document_path',
        'document_name',
        'document_size',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'sent_at' => 'datetime',
            'document_size' => 'integer',
        ];
    }

    /**
     * The prep list this quotation is for.
     */
    public function preplist(): BelongsTo
    {
        return $this->belongsTo(Preplist::class);
    }

    /**
     * The supplier this quotation was sent to.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * The user who generated this quotation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The supplier's quoted line items against the prep list.
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    /**
     * The line items that have a quoted unit price.
     */
    public function quotedItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->whereNotNull('unit_price');
    }
}
