<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    /**
     * activity_logs only has created_at; there is no updated_at column.
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'role_name',
        'action',
        'module',
        'record_label',
        'subject_type',
        'subject_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
