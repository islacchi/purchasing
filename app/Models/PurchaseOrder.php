<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    /**
     * The model does not use created_at / updated_at columns.
     *
     * @var bool
     */
    public $timestamps = false;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'quotation_id',
        'supplier_id',
        'created_by',
        'po_number',
        'status',
        'issued_on',
        'expected_delivery_on',
        'payment_terms',
        'deliver_to',
        'delivery_fee',
        'notes',
        'cancelled_at',
        'cancel_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'expected_delivery_on' => 'date',
            'delivery_fee' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * The quotation this purchase order was generated from.
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * The supplier this purchase order is sent to.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * The user who generated this purchase order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The lines on this purchase order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * The line totals summed from the purchase order's items.
     */
    public function lineTotal(): float
    {
        return (float) $this->items->sum(fn (PurchaseOrderItem $item) => $item->qty * (float) $item->unit_price);
    }

    /**
     * The grand total, delivery fee included.
     */
    public function grandTotal(): float
    {
        return $this->lineTotal() + (float) $this->delivery_fee;
    }

    /**
     * The number of lines on this order.
     */
    public function totalItems(): int
    {
        return $this->items->count();
    }

    /**
     * The human readable workflow status.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SENT => 'Sent to supplier',
            self::STATUS_PARTIALLY_RECEIVED => 'Partially received',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    /**
     * Steps in the happy path of the purchasing workflow.
     *
     * @return list<string>
     */
    public static function stages(): array
    {
        return ['Draft', 'Sent to supplier', 'Partially received', 'Completed'];
    }

    /**
     * Index position of the current status in the workflow.
     */
    public function currentStageIndex(): ?int
    {
        $position = array_search($this->statusLabel(), self::stages(), true);

        return $position === false ? null : $position;
    }

    /**
     * A cancelled order cannot be sent or received.
     */
    public function isCancellable(): bool
    {
        return ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true);
    }

    /**
     * Receipts can only be recorded once the order has been sent.
     */
    public function canReceive(): bool
    {
        return ! in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CANCELLED], true);
    }
}
