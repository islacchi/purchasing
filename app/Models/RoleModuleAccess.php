<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleModuleAccess extends Model
{
    public const LEVEL_NONE = 'none';

    public const LEVEL_VIEW = 'view';

    public const LEVEL_MANAGE = 'manage';

    protected $table = 'role_module_access';

    protected $fillable = [
        'role_id',
        'module',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'role_id' => 'integer',
        ];
    }

    /**
     * Rank of an access level for comparisons (none < view < manage).
     */
    public static function rank(string $level): int
    {
        return match ($level) {
            self::LEVEL_VIEW => 1,
            self::LEVEL_MANAGE => 2,
            default => 0,
        };
    }

    /**
     * The role this grant belongs to.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
