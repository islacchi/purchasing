<?php

namespace App\Support;

use App\Models\RoleModuleAccess;

class ModuleAccess
{
    /**
     * The modules that can be restricted. Settings deliberately stays
     * administrator-only via the access-settings gate and is not part
     * of this map.
     *
     * @var array<string, string>
     */
    public const MODULES = [
        'projects' => 'Projects',
        'preplist' => 'Preplist',
        'quotation' => 'Quotation',
        'priceindex' => 'Price Index',
        'purchase-order' => 'Purchase Order',
        'entities' => 'Entities',
    ];

    /** @var array<string, string> */
    private static array $cache = [];

    /**
     * Drop the per-request level cache (e.g. after the matrix is updated).
     */
    public static function flush(): void
    {
        static::$cache = [];
    }

    /**
     * Return a role's level for a module, from the static per-request cache.
     */
    public static function level(?int $roleId, string $module): string
    {
        if ($roleId === null) {
            return RoleModuleAccess::LEVEL_NONE;
        }

        if (! array_key_exists($roleId.'|'.$module, static::$cache)) {
            static::$cache[$roleId.'|'.$module] = RoleModuleAccess::query()
                ->where('role_id', $roleId)
                ->where('module', $module)
                ->value('level') ?? RoleModuleAccess::LEVEL_NONE;
        }

        return static::$cache[$roleId.'|'.$module];
    }

    /**
     * Whether a role meets the required level for a module.
     */
    public static function allows(?int $roleId, string $module, string $required = RoleModuleAccess::LEVEL_VIEW): bool
    {
        return RoleModuleAccess::rank(self::level($roleId, $module)) >= RoleModuleAccess::rank($required);
    }
}
