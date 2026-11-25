<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Extracts the supplier's unit price for each item from the completed RFQ
 * template that the supplier returned to us.
 *
 * The parser is deliberately strict: the worksheet must contain the RFQ
 * template's header row (located dynamically), and every item row must carry
 * a numeric "No." and match one of the prep list's items by description.
 * Anything else is rejected as a validation error instead of silently
 * guessing.
 */
class QuotationWorkbookParser
{
    /**
     * Read the worksheet and return the parsed item rows.
     *
     * @return array<int, array{description: string, brand: string|null, expiry: string|null, unit_price: float|null, supplier_box_size: int|null, supplier_unit: string|null}>
     */
    public static function parse(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('The uploaded file is not a valid .xlsx workbook.');
        }

        $files = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            $files[$name] = $zip->getFromIndex($index);
        }

        $zip->close();

        $sheetXml = $files['xl/worksheets/sheet1.xml'] ?? null;

        if ($sheetXml === null || $sheetXml === false) {
            throw new RuntimeException('The uploaded file is missing its worksheet.');
        }

        $sharedStrings = self::sharedStrings($files['xl/sharedStrings.xml'] ?? '');
        $dateStyles = self::dateStyles($files['xl/styles.xml'] ?? '');

        $sheet = simplexml_load_string($sheetXml);

        if ($sheet === false) {
            throw new RuntimeException('The uploaded file contains an invalid worksheet.');
        }

        $sheet->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = $sheet->xpath('//m:sheetData/m:row') ?: [];

        $header = self::findHeaderRow($rows, $sharedStrings);

        if ($header === null) {
            throw new RuntimeException('The uploaded file does not look like the RFQ template issued for this quotation.');
        }

        [$headerNumber, $columns] = $header;

        $parsed = [];

        foreach ($rows as $row) {
            $rowNumber = (int) $row['r'];

            if ($rowNumber <= $headerNumber) {
                continue;
            }

            $cells = self::rowCells($row, $sharedStrings);
            $number = trim((string) ($cells['A'] ?? ''));
            $description = trim((string) ($cells['B'] ?? ''));

            // Item rows carry a numeric "No." in column A. Spacer and note
            // rows (including the trailing instructions) don't, so they are
            // skipped.
            if (! is_numeric($number)) {
                continue;
            }

            if ($description === '') {
                throw new RuntimeException('An item row in the uploaded file is missing its description.');
            }

            $parsed[] = [
                'description' => $description,
                'brand' => self::brandValue(self::findCell($row, $columns['brand']), $sharedStrings),
                'expiry' => self::expiryFromCell(
                    self::findCell($row, $columns['expiry']),
                    $sharedStrings,
                    $dateStyles,
                    $rowNumber,
                    $description,
                ),
                'unit_price' => self::unitPrice($cells[$columns['unit_price']] ?? null),
                'supplier_box_size' => self::supplierBoxSize($cells[$columns['supplier_box']] ?? null, $rowNumber, $description),
                'supplier_unit' => isset($columns['supplier_unit'])
                    ? self::supplierUnitValue($cells[$columns['supplier_unit']] ?? null)
                    : null,
            ];
        }

        if ($parsed === []) {
            throw new RuntimeException('No item rows were found in the uploaded file.');
        }

        return $parsed;
    }

    /**
     * Locate the header row and map the recognized layout's columns.
     *
     * @param  array<int, \SimpleXMLElement>  $rows
     * @param  array<int, string>  $sharedStrings
     * @return array{0: int, 1: array<string, string>}|null
     */
    private static function findHeaderRow(array $rows, array $sharedStrings): ?array
    {
        foreach ($rows as $row) {
            $cells = self::rowCells($row, $sharedStrings);

            $columns = self::headerColumns($cells);

            if ($columns !== null) {
                return [(int) $row['r'], $columns];
            }
        }

        return null;
    }

    /**
     * Map the header cells of a recognized RFQ layout to the columns the
     * parser reads. The generator emits "Unit" and "Box Size" columns for
     * boxed preplists and drops them for boxless ones, so the letters of the
     * supplier fields differ between the layouts. Files issued before that
     * change (with the legacy "Supplier Box of" header at I) are still
     * accepted.
     *
     * @param  array<string, string>  $cells
     * @return array<string, string>|null
     */
    private static function headerColumns(array $cells): ?array
    {
        if (trim((string) ($cells['A'] ?? '')) !== 'No.' || trim((string) ($cells['B'] ?? '')) !== 'Item Description') {
            return null;
        }

        // New boxless layout: A No., B Item Description, C Shelf Life,
        // D Brand, E Unit Price (PHP), F Expiry,
        // G "Supplier's Box Size or Packaging", H "Supplier's Unit", I Remarks.
        $newBoxless = isset($cells['G']) && trim((string) $cells['G']) === "Supplier's Box Size or Packaging";

        // New boxed layout: A No., B Item Description, C Unit, D Box Size,
        // E Shelf Life, F Brand, G Unit Price (PHP), H Expiry,
        // I "Supplier's Box Size or Packaging", J "Supplier's Unit", K Remarks.
        $newBoxed = isset($cells['I']) && trim((string) $cells['I']) === "Supplier's Box Size or Packaging";

        // Legacy layout (files issued before the column rework):
        // G = Unit Price (PHP), I = "Supplier Box of".
        $legacy = isset($cells['G']) && trim((string) $cells['G']) === 'Unit Price (PHP)'
            && isset($cells['I']) && trim((string) $cells['I']) === 'Supplier Box of';

        if ($newBoxless) {
            return isset($cells['E']) && trim((string) $cells['E']) === 'Unit Price (PHP)'
                ? ['unit_price' => 'E', 'brand' => 'D', 'expiry' => 'F', 'supplier_box' => 'G', 'supplier_unit' => 'H']
                : null;
        }

        if ($newBoxed) {
            return isset($cells['G']) && trim((string) $cells['G']) === 'Unit Price (PHP)'
                ? ['unit_price' => 'G', 'brand' => 'F', 'expiry' => 'H', 'supplier_box' => 'I', 'supplier_unit' => 'J']
                : null;
        }

        if ($legacy) {
            return ['unit_price' => 'G', 'brand' => 'F', 'expiry' => 'H', 'supplier_box' => 'I'];
        }

        return null;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<string, string|null>
     */
    private static function rowCells(\SimpleXMLElement $row, array $sharedStrings): array
    {
        $cells = [];

        foreach ($row->c as $cell) {
            $reference = (string) $cell['r'];
            $column = preg_replace('/[0-9]+$/', '', $reference);

            if ($column === null || $column === '') {
                continue;
            }

            $cells[$column] = self::cellValue($cell, $sharedStrings);
        }

        return $cells;
    }

    /**
     * Locate a single cell element by its column letter.
     */
    private static function findCell(\SimpleXMLElement $row, string $column): ?\SimpleXMLElement
    {
        foreach ($row->c as $cell) {
            if (preg_replace('/[0-9]+$/', '', (string) $cell['r']) === $column) {
                return $cell;
            }
        }

        return null;
    }

    /**
     * The supplier's brand for an item, trimmed and null when left blank.
     *
     * @param  array<int, string>  $sharedStrings
     */
    private static function brandValue(?\SimpleXMLElement $cell, array $sharedStrings): ?string
    {
        if ($cell === null) {
            return null;
        }

        $value = trim(self::cellValue($cell, $sharedStrings));

        return $value === '' ? null : $value;
    }

    /**
     * The supplier's expiry for an item, normalised to a Y-m-d string (or
     * null). Date-formatted cells are converted from Excel's numeric serial;
     * free text is parsed via the shared ExpiryParser. An unreadable expiry
     * aborts the whole upload instead of silently storing garbage.
     *
     * @param  array<int, string>  $sharedStrings
     * @param  array<int, bool>  $dateStyles
     */
    private static function expiryFromCell(?\SimpleXMLElement $cell, array $sharedStrings, array $dateStyles, int $rowNumber, string $description): ?string
    {
        $raw = self::expiryValue($cell, $sharedStrings, $dateStyles);

        if ($raw === null || trim($raw) === '') {
            return null;
        }

        try {
            return ExpiryParser::parse($raw)?->toDateString();
        } catch (RuntimeException $exception) {
            throw new RuntimeException(
                'Row '.$rowNumber.' ("'.$description.'"): '.$exception->getMessage(),
                0,
                $exception,
            );
        }
    }

    /**
     * The supplier's expiry for an item. Date-formatted cells are converted
     * from Excel's numeric serial to a Y-m-d string; anything else is returned
     * exactly as typed.
     *
     * @param  array<int, string>  $sharedStrings
     * @param  array<int, bool>  $dateStyles
     */
    private static function expiryValue(?\SimpleXMLElement $cell, array $sharedStrings, array $dateStyles): ?string
    {
        if ($cell === null) {
            return null;
        }

        $value = trim(self::cellValue($cell, $sharedStrings));

        if ($value === '') {
            return null;
        }

        $style = (int) ($cell['s'] ?? 0);

        if (is_numeric($value) && ($dateStyles[$style] ?? false)) {
            return self::excelDate((float) $value);
        }

        return $value;
    }

    /**
     * Convert an Excel date serial into a Y-m-d string.
     */
    private static function excelDate(float $serial): string
    {
        $days = (int) floor($serial);
        $seconds = (int) round(($serial - $days) * 86400);
        $timestamp = (($days - 25569) * 86400) + $seconds;

        return gmdate('Y-m-d', $timestamp);
    }

    /**
     * Map each cell style index to whether it renders as a date.
     *
     * @return array<int, bool>
     */
    private static function dateStyles(string $xml): array
    {
        $trimmed = trim($xml);

        if ($trimmed === '') {
            return [];
        }

        $document = new \DOMDocument;

        if (@$document->loadXML($trimmed) === false) {
            return [];
        }

        $xpath = new \DOMXPath($document);

        $customFormats = [];

        foreach ($xpath->query('//*[local-name()="numFmts"]/*[local-name()="numFmt"]') as $numFmt) {
            /** @var \DOMElement $numFmt */
            $customFormats[(int) $numFmt->getAttribute('numFmtId')] = (string) $numFmt->getAttribute('formatCode');
        }

        $builtInDateFormats = [14, 15, 16, 17, 18, 19, 20, 21, 22, 45, 46, 47];
        $styles = [];

        $cellFormats = $xpath->query('//*[local-name()="cellXfs"]/*[local-name()="xf"]');

        if ($cellFormats === false) {
            return [];
        }

        foreach ($cellFormats as $index => $format) {
            /** @var \DOMElement $format */
            $formatId = (int) $format->getAttribute('numFmtId');
            $formatCode = $customFormats[$formatId] ?? null;

            $styles[(int) $index] = in_array($formatId, $builtInDateFormats, true)
                || ($formatCode !== null && self::looksLikeDateFormat($formatCode));
        }

        return $styles;
    }

    /**
     * Whether a custom number format contains date/time tokens.
     */
    private static function looksLikeDateFormat(string $formatCode): bool
    {
        // Ignore quoted literals, escaped characters, and bracketed sections
        // (colors, conditions) before looking for date tokens.
        $cleaned = preg_replace('/"[^"]*"|\\\\.|\[[^\]]*\]/', '', $formatCode) ?? $formatCode;

        return (bool) preg_match('/[ymdhis]/i', $cleaned);
    }

    /**
     * @return array<int, string>
     */
    private static function sharedStrings(string $xml): array
    {
        $trimmed = trim($xml);

        if ($trimmed === '') {
            return [];
        }

        $document = new \DOMDocument;

        if (@$document->loadXML($trimmed) === false) {
            return [];
        }

        $xpath = new \DOMXPath($document);
        $strings = [];

        foreach ($xpath->query('//*[local-name()="si"]') as $si) {
            /** @var \DOMElement $si */
            $parts = [];

            foreach ($si->getElementsByTagName('t') as $textNode) {
                $parts[] = $textNode->textContent;
            }

            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    private static function cellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) ($cell['t'] ?? '');
        $value = isset($cell->v) ? (string) $cell->v : '';

        return match ($type) {
            's' => $sharedStrings[(int) $value] ?? '',
            'inlineStr' => (string) ($cell->is->t ?? ''),
            'str' => $value,
            default => $value,
        };
    }

    /**
     * The supplier's box size for an item (units per box), when the supplier
     * wrote one in the Supplier's Box Size or Packaging column (or the legacy
     * "Supplier Box of" column). A positive whole number wins; anything else
     * aborts the upload with the row number.
     */
    private static function supplierBoxSize(?string $raw, int $rowNumber, string $description): ?int
    {
        if ($raw === null) {
            return null;
        }

        $cleaned = trim($raw);

        if ($cleaned === '') {
            return null;
        }

        if (! preg_match('/^\d+$/', $cleaned)) {
            throw new RuntimeException('Row '.$rowNumber.' ("'.$description.'"): Supplier Box of must be a whole number of units, e.g. 50.');
        }

        $value = (int) $cleaned;

        if ($value < 1) {
            throw new RuntimeException('Row '.$rowNumber.' ("'.$description.'"): Supplier Box of must be at least 1 unit.');
        }

        return $value;
    }

    /**
     * The unit the supplier filled in the quotation's "Supplier's Unit"
     * column, when they wrote one. Free text (e.g. "Box of 50"); blank or
     * missing means none was given.
     */
    private static function supplierUnitValue(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $cleaned = trim($raw);

        return $cleaned === '' ? null : $cleaned;
    }

    private static function unitPrice(?string $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        // Strip currency symbols, thousands separators, and any surrounding text.
        $cleaned = preg_replace('/[^0-9.\-]/', '', $raw);

        if ($cleaned === '' || $cleaned === '-' || $cleaned === '.') {
            return null;
        }

        if (! is_numeric($cleaned)) {
            return null;
        }

        return round((float) $cleaned, 2);
    }
}
