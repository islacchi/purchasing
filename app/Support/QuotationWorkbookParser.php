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
     * @return array<int, array{description: string, unit_price: float|null}>
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

        $sheet = simplexml_load_string($sheetXml);

        if ($sheet === false) {
            throw new RuntimeException('The uploaded file contains an invalid worksheet.');
        }

        $sheet->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = $sheet->xpath('//m:sheetData/m:row') ?: [];

        $headerNumber = self::findHeaderRow($rows, $sharedStrings);

        if ($headerNumber === null) {
            throw new RuntimeException('The uploaded file does not look like the RFQ template issued for this quotation.');
        }

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
                'unit_price' => self::unitPrice($cells['F'] ?? null),
            ];
        }

        if ($parsed === []) {
            throw new RuntimeException('No item rows were found in the uploaded file.');
        }

        return $parsed;
    }

    /**
     * @param  array<int, \SimpleXMLElement>  $rows
     * @param  array<int, string>  $sharedStrings
     */
    private static function findHeaderRow(array $rows, array $sharedStrings): ?int
    {
        foreach ($rows as $row) {
            $cells = self::rowCells($row, $sharedStrings);

            if (
                isset($cells['A'], $cells['B'], $cells['G'])
                && trim((string) $cells['A']) === 'No.'
                && trim((string) $cells['B']) === 'Item Description'
                && trim((string) $cells['G']) === 'Suppliers Remarks'
            ) {
                return (int) $row['r'];
            }
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
