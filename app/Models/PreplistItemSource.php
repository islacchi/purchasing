<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreplistItemSource extends Model
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
        'preplist_item_id',
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
     * The prep list item this source contributes to.
     */
    public function preplistItem(): BelongsTo
    {
        return $this->belongsTo(PreplistItem::class);
    }

    /**
     * The project item that supplies this quantity.
     */
    public function projectItem(): BelongsTo
    {
        return $this->belongsTo(ProjectItem::class);
    }
}
