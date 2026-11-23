<?php

namespace App\Models;

use App\Support\BoxQuantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreplistItem extends Model
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
        'item_id',
        'min_shelf_life_months',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_shelf_life_months' => 'integer',
        ];
    }

    /**
     * The prep list this item belongs to.
     */
    public function preplist(): BelongsTo
    {
        return $this->belongsTo(Preplist::class);
    }

    /**
     * The catalog item referenced by this prep list item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * The project item quantities that make up this prep list item.
     */
    public function sources(): HasMany
    {
        return $this->hasMany(PreplistItemSource::class);
    }

    /**
     * Quantity still to procure after deducting stock-on-hand from each source.
     * Returned in the requirement unit: boxes for boxed items, units otherwise.
     */
    public function remainingQuantity(): int
    {
        return BoxQuantity::toBoxes($this->remainingPieces(), $this->boxSize());
    }

    /**
     * Base pieces still to procure across every source project line. This is
     * the exact figure purchases are reconciled against, regardless of the
     * pack size bought.
     */
    public function remainingPieces(): int
    {
        return (int) $this->sources->sum(
            fn (PreplistItemSource $source) => $source->projectItem?->remainingPieces() ?? 0
        );
    }

    /**
     * The item's box-size requirement, when it has one.
     */
    public function boxSize(): ?int
    {
        $boxSize = $this->item?->box_size;

        return $boxSize !== null && (int) $boxSize > 0 ? (int) $boxSize : null;
    }

    /**
     * Sum of the quantities contributed by the source projects.
     */
    public function totalQuantity(): int
    {
        return (int) $this->sources->sum('qty');
    }
}
