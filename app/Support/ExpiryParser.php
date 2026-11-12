<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use RuntimeException;

class ExpiryParser
{
    private const BLANK_TOKENS = ['', '-', '—', '–', 'n/a', 'na', 'none', 'nil'];

    /**
     * Accepted inputs (case-insensitive where letters appear, leading/trailing
     * whitespace ignored):
     *
     *   Exact dates (day required):
     *     - 2027-02-02          ISO, zero-padded month and day
     *     - 15 Feb 2027         "DD Mon YYYY" / "DD Month YYYY" (optional dot
     *                            after the month abbreviation)
     *     - Feb 15, 2027        "Mon DD, YYYY" / "Month DD, YYYY"
     *   Month-only expiries (last day of the month):
     *     - 02/2027             MM/YYYY
     *     - 2027-02             YYYY-MM
     *     - Feb 2027            "Mon YYYY" / "Month YYYY" (optional dot)
     *   Blank tokens (return null):
     *     - '', '-', '—', '–', 'n/a', 'na', 'none', 'nil'
     *   Rejected as ambiguous (never parsed):
     *     - 03/04/2027, 03.04.2027, 03-04-2027, DD/MM/YYYY, DD.MM.YYYY, DD-MM-YYYY
     *   Rejected as unreadable:
     *     - anything else, including 2027-2-2, 2027-13-01, 30 Feb 2027,
     *       letters that are not months, and mixed token garbage
     */
    public static function parse(?string $value): ?CarbonImmutable
    {
        $value = trim((string) $value);

        if (in_array(mb_strtolower($value), self::BLANK_TOKENS, true)) {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return self::exactDate((int) $m[1], (int) $m[2], (int) $m[3], $value);
        }

        if (preg_match('/^(\d{1,2})\/(\d{4})$/', $value, $m)) {
            return self::monthOnly((int) $m[2], (int) $m[1], $value);
        }

        if (preg_match('/^(\d{4})-(\d{1,2})$/', $value, $m)) {
            return self::monthOnly((int) $m[1], (int) $m[2], $value);
        }

        if (preg_match('/^([A-Za-z]{3,9})\.?\s+(\d{1,2}),?\s+(\d{4})$/', $value, $m)) {
            $month = self::monthNumber($m[1]);

            if ($month === null) {
                throw self::unreadable($value);
            }

            return self::exactDate((int) $m[3], $month, (int) $m[2], $value);
        }

        if (preg_match('/^([A-Za-z]{3,9})\.?\s+(\d{4})$/', $value, $m)) {
            $month = self::monthNumber($m[1]);

            if ($month === null) {
                throw self::unreadable($value);
            }

            return CarbonImmutable::create((int) $m[2], $month, 1)->endOfMonth()->startOfDay();
        }

        if (preg_match('/^(\d{1,2})\s+([A-Za-z]{3,9})\.?\s+(\d{4})$/', $value, $m)) {
            $month = self::monthNumber($m[2]);

            if ($month === null) {
                throw self::unreadable($value);
            }

            return self::exactDate((int) $m[3], $month, (int) $m[1], $value);
        }

        if (preg_match('/^\d{1,2}[\/.\-]\d{1,2}[\/.\-]\d{2,4}$/', $value)) {
            throw new RuntimeException(
                '"'.$value.'" is ambiguous (day/month or month/day). Use YYYY-MM-DD, or write the month in letters, e.g. 3 Apr 2027.'
            );
        }

        throw self::unreadable($value);
    }

    private static function exactDate(int $year, int $month, int $day, string $original): CarbonImmutable
    {
        if (! checkdate($month, $day, $year)) {
            throw self::unreadable($original);
        }

        return CarbonImmutable::create($year, $month, $day)->startOfDay();
    }

    private static function monthOnly(int $year, int $month, string $original): CarbonImmutable
    {
        if ($month < 1 || $month > 12) {
            throw self::unreadable($original);
        }

        return CarbonImmutable::create($year, $month, 1)->endOfMonth()->startOfDay();
    }

    /**
     * Case-insensitive month name (full or short) to month number.
     */
    private static function monthNumber(string $month): ?int
    {
        $name = mb_strtolower(rtrim($month, '.'));

        $map = [
            'jan' => 1, 'january' => 1,
            'feb' => 2, 'february' => 2,
            'mar' => 3, 'march' => 3,
            'apr' => 4, 'april' => 4,
            'may' => 5,
            'jun' => 6, 'june' => 6,
            'jul' => 7, 'july' => 7,
            'aug' => 8, 'august' => 8,
            'sep' => 9, 'sept' => 9, 'september' => 9,
            'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
            'dec' => 12, 'december' => 12,
        ];

        return $map[$name] ?? null;
    }

    private static function unreadable(string $value): RuntimeException
    {
        return new RuntimeException(
            '"'.$value.'" is not an expiry date that can be read. Use YYYY-MM-DD, MM/YYYY, or e.g. Feb 2027.'
        );
    }
}
