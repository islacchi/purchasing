<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Shared per-page handling for server-side paginated lists.
 */
class Pagination
{
    /**
     * Allowed per-page options shown in each module's "Per page" selector.
     *
     * @var array<int, int>
     */
    public const ALLOWED = [5, 10, 25, 50, 100];

    /**
     * Resolve the per-page limit from the (whitelisted) per_page query parameter.
     */
    public static function perPage(Request $request, int $default = 10): int
    {
        $value = (int) $request->query('per_page', $default);

        return in_array($value, self::ALLOWED, true) ? $value : $default;
    }

    /**
     * Options for the selector as value => label.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return array_combine(self::ALLOWED, array_map('strval', self::ALLOWED));
    }
}
