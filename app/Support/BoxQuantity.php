<?php

namespace App\Support;

/**
 * Base-piece maths for boxed requirements.
 *
 * Items without a box size are counted in plain units; for those items every
 * helper here is a pass-through. Items with a box size ("Box of 20") keep
 * their requirement in boxes, but purchases can arrive in any pack, so the
 * exact quantities are tracked in base pieces: 100 boxes of 20 = 2,000 pieces.
 */
final class BoxQuantity
{
    /**
     * Whether the given pack size is a real box requirement.
     */
    public static function hasBox(?int $boxSize): bool
    {
        return $boxSize !== null && $boxSize > 0;
    }

    /**
     * Convert a number of boxes to base pieces (or pass units through).
     */
    public static function toPieces(int $boxes, ?int $boxSize): int
    {
        return self::hasBox($boxSize) ? $boxes * (int) $boxSize : $boxes;
    }

    /**
     * Convert base pieces back to whole boxes needed to cover them (or pass
     * units through). Rounds up: a partial box still has to be bought.
     */
    public static function toBoxes(int $pieces, ?int $boxSize): int
    {
        return self::hasBox($boxSize) ? (int) ceil($pieces / (int) $boxSize) : $pieces;
    }

    /**
     * Human-readable pieces with their box equivalent, e.g.
     * "1,600 pcs (80 boxes of 20)" or "1,610 pcs (80 boxes + 10 pcs)".
     */
    public static function describePieces(int $pieces, ?int $boxSize, bool $includePack = true): string
    {
        $pieces = max(0, $pieces);

        if (! self::hasBox($boxSize)) {
            return number_format($pieces);
        }

        $boxSize = (int) $boxSize;
        $boxes = intdiv($pieces, $boxSize);
        $rest = $pieces % $boxSize;

        $label = number_format($pieces).' pcs';

        if ($boxes > 0) {
            $label .= ' ('.number_format($boxes).' box'.($boxes === 1 ? '' : 'es');
            if ($includePack) {
                $label .= ' of '.$boxSize;
            }
            if ($rest > 0) {
                $label .= ' + '.number_format($rest).' pc'.($rest === 1 ? '' : 's');
            }

            return $label.')';
        }

        return $label;
    }

    /**
     * Human-readable boxes with their piece equivalent, e.g.
     * "100 boxes (2,000 pcs)".
     */
    public static function describeBoxes(int $boxes, ?int $boxSize): string
    {
        if (! self::hasBox($boxSize)) {
            return number_format($boxes);
        }

        return number_format($boxes).' box'.($boxes === 1 ? '' : 'es')
            .' ('.number_format(self::toPieces($boxes, $boxSize)).' pcs)';
    }
}
