<?php

namespace App\Models;

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
     * Sum of the quantities contributed by the source projects.
     */
    public function totalQuantity(): int
    {
        return (int) $this->sources->sum('qty');
    }
}
