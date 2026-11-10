<?php

namespace App\Support;

use App\Models\Preplist;
use App\Models\PreplistItem;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use ZipArchive;

/**
 * Builds a real .xlsx "Request for Quotation" workbook for one supplier by
 * filling in the project's RFQ template (resources/templates/RFQ-Template.xlsx).
 *
 * The template carries the company branding, logo, fonts, fills, and column
 * widths, so we reuse it as the base document and only replace the worksheet's
 * row data. Quantities are deliberately excluded: suppliers receive the item
 * list, the unit, the required minimum shelf-life at delivery, and quote a
 * firm unit price. Quoted prices stay internal once the supplier returns
 * the form.
 */
class RfqQuotationWorkbook
{
    /**
     * @param  Collection<int, PreplistItem>  $items
     */
    public static function build(Preplist $preplist, Supplier $supplier, Collection $items): string
    {
        $sheet = self::sheetXml($preplist, $supplier, $items);

        $files = [];
        $templatePath = resource_path('templates/RFQ-Template.xlsx');

        if (! file_exists($templatePath)) {
            throw new \RuntimeException('The RFQ template is missing. Expected resources/templates/RFQ-Template.xlsx.');
        }

        $template = new ZipArchive;

        if ($template->open($templatePath) !== true) {
            throw new \RuntimeException('Unable to open the RFQ template.');
        }

        for ($index = 0; $index < $template->numFiles; $index++) {
            $name = $template->getNameIndex($index);

            if ($name === 'xl/worksheets/sheet1.xml') {
                continue;
            }

            $contents = $template->getFromIndex($index);

            if ($contents !== false) {
                $files[$name] = $contents;
            }
        }

        $template->close();

        $files['xl/worksheets/sheet1.xml'] = $sheet;

        return self::zip($files);
    }

    /**
     * @param  Collection<int, PreplistItem>  $items
     */
    private static function sheetXml(Preplist $preplist, Supplier $supplier, Collection $items): string
    {
        $rows = [];
        $rowNumber = 1;

        $rows[] = self::row($rowNumber++, [
            self::cell('A1', 14, null),
            self::cell('B1', 14, null),
            self::cell('C1', 14, null),
            self::cell('D1', 14, null),
            self::cell('E1', 14, null),
            self::cell('F1', 14, null),
            self::cell('G1', 14, null),
            self::cell('H1', 14, null),
        ], 71.25);

        $rows[] = self::row($rowNumber++, [
            self::cell('A2', 14, '0042 F. Toledo St. Dao, Tagbilaran City, Bohol'),
            self::cell('B2', 14, null),
            self::cell('C2', 14, null),
            self::cell('D2', 14, null),
            self::cell('E2', 14, null),
            self::cell('F2', 14, null),
            self::cell('G2', 14, null),
            self::cell('H2', 14, null),
        ], 15);

        $rows[] = self::row($rowNumber++, [
            self::cell('A3', 14, 'Telephone No: (038) 502-2781 ; Mobile Nos. 09204295864;'),
            self::cell('B3', 14, null),
            self::cell('C3', 14, null),
            self::cell('D3', 14, null),
            self::cell('E3', 14, null),
            self::cell('F3', 14, null),
            self::cell('G3', 14, null),
            self::cell('H3', 14, null),
        ], 13.5);

        $rows[] = self::row($rowNumber++, [
            self::cell('A4', 14, 'Email Address: plpharma_ph@yahoo.com'),
            self::cell('B4', 14, null),
            self::cell('C4', 14, null),
            self::cell('D4', 14, null),
            self::cell('E4', 14, null),
            self::cell('F4', 14, null),
            self::cell('G4', 14, null),
            self::cell('H4', 14, null),
        ], 13.5);

        $rows[] = self::row($rowNumber++, [
            self::cell('A5', 14, null),
            self::cell('B5', 14, null),
            self::cell('C5', 14, null),
            self::cell('D5', 14, null),
            self::cell('E5', 14, null),
            self::cell('F5', 14, null),
            self::cell('G5', 14, null),
            self::cell('H5', 14, null),
        ], 28.5);

        $rows[] = self::row($rowNumber++, [
            self::cell('A6', 15, 'REQUEST FOR QUOTATION'),
            self::cell('B6', 15, null),
            self::cell('C6', 15, null),
            self::cell('D6', 15, null),
            self::cell('E6', 15, null),
            self::cell('F6', 15, null),
            self::cell('G6', 15, null),
            self::cell('H6', 15, null),
        ], 21);

        $rows[] = self::row($rowNumber++, [
            self::cell('A7', 16, 'SUPPLIER QUOTATION'),
            self::cell('B7', 16, null),
            self::cell('C7', 16, null),
            self::cell('D7', 16, null),
            self::cell('E7', 16, null),
            self::cell('F7', 16, null),
            self::cell('G7', 16, null),
            self::cell('H7', 16, null),
        ], 16.5);

        $rows[] = self::customFormatRow($rowNumber++, [
            self::cell('A8', 6, null),
            self::cell('B8', 6, null),
            self::cell('C8', 6, null),
            self::cell('D8', 6, null),
            self::cell('E8', 6, null),
            self::cell('F8', 6, null),
            self::cell('G8', 6, null),
        ], 21);

        $rows[] = self::blankRow($rowNumber++, 10.5);

        $rows[] = self::row($rowNumber++, [
            self::cell('A10', 10, 'Prep List'),
            self::cell('B10', 9, $preplist->reference_no.' - '.$preplist->name),
            self::cell('C10', 7, null),
            self::cell('D10', 7, null),
            self::cell('E10', 7, null),
            self::cell('F10', 7, null),
        ], 26.25);

        $rows[] = self::row($rowNumber++, [
            self::cell('A11', 10, 'Date'),
            self::cell('B11', 9, now()->format('F j, Y')),
            self::cell('C11', 5, null),
            self::cell('D11', 5, null),
            self::cell('E11', 5, null),
            self::cell('F11', 5, null),
        ], 22.5);

        $rows[] = self::row($rowNumber++, [
            self::cell('A12', 10, 'Supplier'),
            self::cell('B12', 9, $supplier->name),
            self::cell('C12', 5, null),
            self::cell('D12', 5, null),
            self::cell('E12', 5, null),
            self::cell('F12', 5, null),
        ], 23.25);

        $rows[] = self::customFormatRow($rowNumber++, [
            self::cell('A13', 1, 'Contact person'),
            self::cell('B13', 9, $supplier->contact_person ?: null),
        ], 23.25);

        $rows[] = self::customFormatRow($rowNumber++, [
            self::cell('A14', 1, null),
            self::cell('B14', 8, null),
        ], 11.25);

        $rows[] = self::row($rowNumber++, [
            self::cell('A15', 12, 'Please quote your best firm unit price for the items below. Quantities are not disclosed; indicate the minimum shelf-life you can comply with at delivery.'),
            self::cell('B15', 13, null),
            self::cell('C15', 13, null),
            self::cell('D15', 13, null),
            self::cell('E15', 13, null),
            self::cell('F15', 13, null),
        ], 30);

        $rows[] = self::blankRow($rowNumber++, 3.95);

        $headerRow = $rowNumber++;
        $rows[] = self::row($headerRow, [
            self::cell('A'.$headerRow, 2, 'No.'),
            self::cell('B'.$headerRow, 2, 'Item Description'),
            self::cell('C'.$headerRow, 2, 'Unit'),
            self::cell('D'.$headerRow, 2, 'Min. Shelf Life at Delivery'),
            self::cell('E'.$headerRow, 2, 'Brand'),
            self::cell('F'.$headerRow, 2, 'Unit Price (PHP)'),
            self::cell('G'.$headerRow, 2, 'Expiry'),
            self::cell('H'.$headerRow, 2, 'Suppliers Remarks'),
        ], 30);

        foreach ($items->values() as $index => $preplistItem) {
            $itemRow = $headerRow + $index + 1;
            $itemName = $preplistItem->item?->name ?? 'Item';
            $unit = $preplistItem->item?->unit ?? '';
            $shelfLife = (int) $preplistItem->min_shelf_life_months;
            $requirement = $shelfLife > 0 ? '≥ '.$shelfLife.' months' : 'Non-perishable';

            $rows[] = self::row($itemRow, [
                self::cell('A'.$itemRow, 4, (string) ($index + 1)),
                self::cell('B'.$itemRow, 3, $itemName),
                self::cell('C'.$itemRow, 4, $unit !== '' ? $unit : null),
                self::cell('D'.$itemRow, 4, $requirement),
                self::cell('E'.$itemRow, 4, null),
                self::cell('F'.$itemRow, 4, null),
                self::cell('G'.$itemRow, 4, null),
                self::cell('H'.$itemRow, 4, null),
            ], 18);
        }

        $blankRow = $headerRow + $items->count() + 1;
        $rows[] = self::blankRow($blankRow, 17.25);

        $noteRow = $blankRow + 1;
        $rows[] = self::row($noteRow, [
            self::cell('A'.$noteRow, 11, 'IMPORTANT: Please use and send this exact Excel file. Do not copy this data into a new workbook or modify the file structure, as doing so will break the system upload formatting.'),
            self::cell('B'.$noteRow, 11, null),
            self::cell('C'.$noteRow, 11, null),
            self::cell('D'.$noteRow, 11, null),
            self::cell('E'.$noteRow, 11, null),
            self::cell('F'.$noteRow, 11, null),
            self::cell('G'.$noteRow, 11, null),
            self::cell('H'.$noteRow, 11, null),
        ], 27.95);

        $merges = [
            'A1:H1',
            'A2:H2',
            'A3:H3',
            'A4:H4',
            'A5:H5',
            'A6:H6',
            'A7:H7',
            'A15:F15',
            'A'.$noteRow.':H'.$noteRow,
        ];

        $mergeCells = '<mergeCells count="'.count($merges).'">';
        foreach ($merges as $merge) {
            $mergeCells .= '<mergeCell ref="'.$merge.'"/>';
        }
        $mergeCells .= '</mergeCells>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x14ac" xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac">'
            .'<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            .'<dimension ref="A1:H'.$noteRow.'"/>'
            .'<sheetViews><sheetView tabSelected="1" view="pageLayout" zoomScaleNormal="100" workbookViewId="0"><selection activeCell="A6" sqref="A6:H6"/></sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15" x14ac:dyDescent="0.25"/>'
            .'<cols>'
            .'<col min="1" max="1" width="15.28515625" customWidth="1"/>'
            .'<col min="2" max="2" width="55" customWidth="1"/>'
            .'<col min="3" max="3" width="14" customWidth="1"/>'
            .'<col min="4" max="5" width="24" customWidth="1"/>'
            .'<col min="6" max="6" width="22" customWidth="1"/>'
            .'<col min="7" max="7" width="19.28515625" customWidth="1"/>'
            .'<col min="8" max="8" width="15.85546875" customWidth="1"/>'
            .'</cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .$mergeCells
            .'<pageMargins left="0.16" right="0.16" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            .'<pageSetup scale="71" orientation="landscape" r:id="rId1"/>'
            .'<drawing r:id="rId2"/>'
            .'</worksheet>';
    }

    /**
     * @param  array<int, string>  $cells
     */
    private static function row(int $number, array $cells, float $height): string
    {
        return '<row r="'.$number.'" spans="1:8" ht="'.self::number($height).'" customHeight="1" x14ac:dyDescent="0.25">'
            .implode('', $cells)
            .'</row>';
    }

    /**
     * @param  array<int, string>  $cells
     */
    private static function customFormatRow(int $number, array $cells, float $height): string
    {
        return '<row r="'.$number.'" spans="1:8" s="5" customFormat="1" ht="'.self::number($height).'" customHeight="1" x14ac:dyDescent="0.25">'
            .implode('', $cells)
            .'</row>';
    }

    private static function blankRow(int $number, float $height): string
    {
        return '<row r="'.$number.'" spans="1:8" ht="'.self::number($height).'" customHeight="1" x14ac:dyDescent="0.25"/>';
    }

    private static function cell(string $reference, int $style, ?string $value): string
    {
        if ($value === null) {
            return '<c r="'.$reference.'" s="'.$style.'"/>';
        }

        return '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.self::escape($value).'</t></is></c>';
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(sprintf('%.2f', $value), '0'), '.');
    }

    /**
     * @param  array<string, string>  $files
     */
    private static function zip(array $files): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'xlsx_');

        if ($temporaryPath === false) {
            throw new \RuntimeException('Unable to create a temporary file for the Excel export.');
        }

        $zip = new ZipArchive;

        if ($zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create the Excel export.');
        }

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        $contents = file_get_contents($temporaryPath);
        unlink($temporaryPath);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read the Excel export.');
        }

        return $contents;
    }
}
