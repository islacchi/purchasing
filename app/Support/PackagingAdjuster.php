<?php

namespace App\Support;

/**
 * Packaging maths shared by the quotation comparison, the quotation detail,
 * and the PO cart checkout. When the supplier's pack (their box) differs
 * from ours, the requirement expressed in *their* boxes can drift away from
 * the requirement expressed in ours — the adjustment spells out how many of
 * their boxes are needed to cover the same need.
 */
class PackagingAdjuster
{
    /**
     * @return array{ourBox: int, theirBox: int, requested: int, neededBoxes: int, totalUnits: int, over: int}|null
     *         null when there is nothing to adjust (our requirement is not
     *         packaged, or the supplier accepted the requirement).
     */
    public static function suggestion(?int $itemBoxSize, ?int $supplierBoxSize, int $requestedOurBoxes): ?array
    {
        if ($itemBoxSize === null || $itemBoxSize < 1) {
            return null;
        }

        if ($supplierBoxSize === null || $supplierBoxSize < 1) {
            return null;
        }

        if ($supplierBoxSize === $itemBoxSize) {
            return null;
        }

        $totalUnits = $requestedOurBoxes * $itemBoxSize;
        $theirBoxes = (int) ceil($totalUnits / $supplierBoxSize);

        return [
            'ourBox' => $itemBoxSize,
            'theirBox' => $supplierBoxSize,
            'requested' => $requestedOurBoxes,
            'neededBoxes' => $theirBoxes,
            'totalUnits' => $totalUnits,
            'over' => $theirBoxes * $supplierBoxSize - $totalUnits,
        ];
    }
}
