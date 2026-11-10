<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Preplist extends Model
{
    use SoftDeletes;

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
        'created_by',
        'name',
        'reference_no',
        'notes',
    ];

    /**
     * The user who compiled this prep list.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The distinct items compiled into this prep list.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PreplistItem::class);
    }

    /**
     * Distinct projects that contributed items to this prep list.
     *
     * @return Collection<int, Project>
     */
    public function sourceProjects(): Collection
    {
        return $this->items
            ->flatMap(fn (PreplistItem $item) => $item->sources)
            ->map(fn (PreplistItemSource $source) => $source->projectItem?->project)
            ->filter()
            ->unique('id')
            ->values();
    }
}
