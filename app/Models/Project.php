<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
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
        'procuring_entity_id',
        'created_by',
        'title',
        'reference_no',
        'mode_of_procurement',
        'amount_awarded',
        'date_awarded',
        'delivery_days',
        'delivery_due_date',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_awarded' => 'decimal:2',
            'date_awarded' => 'date',
            'delivery_days' => 'integer',
            'delivery_due_date' => 'date',
        ];
    }

    /**
     * The procuring entity this project belongs to.
     */
    public function procuringEntity(): BelongsTo
    {
        return $this->belongsTo(ProcuringEntity::class);
    }

    /**
     * The user who created this project.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The items attached to this project.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProjectItem::class);
    }

    /**
     * Total number of item rows attached to the project.
     */
    public function totalItemCount(): int
    {
        return $this->items->count();
    }

    /**
     * Number of item rows already marked as fulfilled.
     */
    public function fulfilledItemCount(): int
    {
        return $this->items
            ->filter(fn (ProjectItem $item) => $item->isFulfilled())
            ->count();
    }

    /**
     * Percentage of item rows already fulfilled.
     */
    public function progressPercentage(): float
    {
        $total = $this->totalItemCount();

        return $total > 0 ? ($this->fulfilledItemCount() / $total) * 100 : 0.0;
    }

    /**
     * Human-readable status derived from the project's item fulfillment.
     */
    public function statusLabel(): string
    {
        $total = $this->totalItemCount();
        $fulfilled = $this->fulfilledItemCount();

        if ($total === 0) {
            return 'Not started';
        }

        if ($fulfilled >= $total) {
            return 'Completed';
        }

        return $fulfilled > 0 ? 'In progress' : 'Pending';
    }

    /**
     * Tailwind color key used by the status pill.
     */
    public function statusColor(): string
    {
        return match ($this->statusLabel()) {
            'Completed' => 'green',
            'In progress' => 'blue',
            default => 'gray',
        };
    }
}
