<?php

namespace App\Support;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Collection;
use ZipArchive;

/**
 * Builds a real .xlsx purchase order workbook for one supplier by filling
 * in the project's PO template (resources/templates/PO-Template.xlsx), which
 * carries the company letterhead, branding, terms and conditions, and the
 * supplier acknowledgment block.
 *
 * Quantities are disclosed here (unlike the RFQ) and prices, extended
 * amounts and totals are snapshot values taken from the PO lines.
 */
class PurchaseOrderWorkbook
{
    private const TERMS = [
        '1. Deliver items strictly according to the specifications, quantities, and brands indicated in this Purchase Order.',
        '2. Brand, manufacturer, specification, or packaging substitutions require prior written approval.',
        '3. Pharmaceutical products must have applicable regulatory authorization/registration.',
        '4. Products must meet the required remaining shelf life stated in the PO or agreed in writing.',
        '5. Items must be properly packed and delivered in good condition.',
        '6. Please provide the applicable Delivery Receipt, Sales Invoice/Official Receipt, and required supporting documents.',
        '7. Discrepancies in quantity, specification, brand, packaging, or condition may be subject to return/replacement.',
        '8. Payment shall be processed according to the agreed payment terms.',
    ];

    private const ACKNOWLEDGMENT_TEXT = 'We acknowledge receipt and acceptance of this Purchase Order and agree to supply the items according to the stated specifications, quantities, prices, and terms.';

    /**
     * @param  Collection<int, PurchaseOrderItem>  $items
     */
    public static function build(PurchaseOrder $purchaseOrder, Collection $items): string
    {
        $sheet = self::sheetXml($purchaseOrder, $items);

        $files = [];
        $templatePath = resource_path('templates/PO-Template.xlsx');

        if (! file_exists($templatePath)) {
            throw new \RuntimeException('The PO template is missing. Expected resources/templates/PO-Template.xlsx.');
        }

        $template = new ZipArchive;

        if ($template->open($templatePath) !== true) {
            throw new \RuntimeException('Unable to open the PO template.');
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
     * @param  Collection<int, PurchaseOrderItem>  $items
     */
    private static function sheetXml(PurchaseOrder $purchaseOrder, Collection $items): string
    {
        $purchaseOrder->loadMissing(['quotation.preplist', 'supplier']);

        $quotation = $purchaseOrder->quotation;
        $supplier = $purchaseOrder->supplier;

        $rows = [];
        $rowNumber = 1;

        $rows[] = self::row($rowNumber++, [
            self::cell('A1', 17, null),
            self::cell('B1', 17, null),
            self::cell('C1', 17, null),
            self::cell('D1', 17, null),
            self::cell('E1', 17, null),
            self::cell('F1', 17, null),
            self::cell('G1', 17, null),
            self::cell('H1', 17, null),
        ], 71.25);

        $rows[] = self::row($rowNumber++, [
            self::cell('A2', 17, '0042 F. Toledo St. Dao, Tagbilaran City, Bohol'),
            self::cell('B2', 17, null),
            self::cell('C2', 17, null),
            self::cell('D2', 17, null),
            self::cell('E2', 17, null),
            self::cell('F2', 17, null),
            self::cell('G2', 17, null),
            self::cell('H2', 17, null),
        ], 15);

        $rows[] = self::row($rowNumber++, [
            self::cell('A3', 17, 'Telephone No: (038) 502-2781 ; Mobile Nos. 09204295864;'),
            self::cell('B3', 17, null),
            self::cell('C3', 17, null),
            self::cell('D3', 17, null),
            self::cell('E3', 17, null),
            self::cell('F3', 17, null),
            self::cell('G3', 17, null),
            self::cell('H3', 17, null),
        ], 13.5);

        $rows[] = self::row($rowNumber++, [
            self::cell('A4', 17, 'Email Address: plpharma_ph@yahoo.com'),
            self::cell('B4', 17, null),
            self::cell('C4', 17, null),
            self::cell('D4', 17, null),
            self::cell('E4', 17, null),
            self::cell('F4', 17, null),
            self::cell('G4', 17, null),
            self::cell('H4', 17, null),
        ], 13.5);

        $rows[] = self::row($rowNumber++, [
            self::cell('A5', 17, null),
            self::cell('B5', 17, null),
            self::cell('C5', 17, null),
            self::cell('D5', 17, null),
            self::cell('E5', 17, null),
            self::cell('F5', 17, null),
            self::cell('G5', 17, null),
            self::cell('H5', 17, null),
        ], 28.5);

        $rows[] = self::row($rowNumber++, [
            self::cell('A6', 13, 'PURCHASE ORDER'),
            self::cell('B6', 13, null),
            self::cell('C6', 13, null),
            self::cell('D6', 13, null),
            self::cell('E6', 13, null),
            self::cell('F6', 13, null),
            self::cell('G6', 13, null),
            self::cell('H6', 13, null),
        ], 21);

        $rows[] = self::row($rowNumber++, [
            self::cell('A7', 14, 'SUPPLIER PURCHASE ORDER'),
            self::cell('B7', 14, null),
            self::cell('C7', 14, null),
            self::cell('D7', 14, null),
            self::cell('E7', 14, null),
            self::cell('F7', 14, null),
            self::cell('G7', 14, null),
            self::cell('H7', 14, null),
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

        // Label / value block.
        $rows[] = self::row($rowNumber++, [
            self::cell('A10', 9, 'PO Number'),
            self::cell('B10', 8, $purchaseOrder->po_number),
            self::cell('C10', 7, null),
            self::cell('D10', 7, null),
            self::cell('E10', 7, null),
            self::cell('F10', 7, null),
        ], 22.5);

        $rows[] = self::row($rowNumber++, [
            self::cell('A11', 9, 'Date Issued'),
            self::cell('B11', 8, $purchaseOrder->issued_on?->format('F j, Y') ?? now()->format('F j, Y')),
            self::cell('C11', 7, null),
            self::cell('D11', 7, null),
            self::cell('E11', 7, null),
            self::cell('F11', 7, null),
        ], 22.5);

        $rows[] = self::row($rowNumber++, [
            self::cell('A12', 9, 'Supplier'),
            self::cell('B12', 8, $supplier?->name ?? 'Supplier removed'),
            self::cell('C12', 7, null),
            self::cell('D12', 7, null),
            self::cell('E12', 7, null),
            self::cell('F12', 7, null),
        ], 23.25);

        $rows[] = self::customFormatRow($rowNumber++, [
            self::cell('A13', 1, 'Contact person'),
            self::cell('B13', 8, $supplier?->contact_person ?: null),
            self::cell('C13', 7, null),
            self::cell('D13', 7, null),
            self::cell('E13', 7, null),
            self::cell('F13', 7, null),
        ], 23.25, 11);

        $rows[] = self::customFormatRow($rowNumber++, [
            self::cell('A14', 1, 'Delivery address'),
            self::cell('B14', 8, $purchaseOrder->deliver_to ?: 'To be scheduled'),
        ], 23.25, 5);

        // Note: expected delivery / payment terms only live on our side, so
        // they are intentionally not printed on the supplier's copy.

        $rows[] = self::blankRow($rowNumber++, 3.95);

        $headerRow = $rowNumber++;
        $rows[] = self::row($headerRow, [
            self::cell('A'.$headerRow, 2, 'No.'),
            self::cell('B'.$headerRow, 2, 'Item Description'),
            self::cell('C'.$headerRow, 2, 'Unit'),
            self::cell('D'.$headerRow, 2, 'Qty'),
            self::cell('E'.$headerRow, 2, 'Min. Shelf Life at Delivery'),
            self::cell('F'.$headerRow, 2, 'Unit Price (PHP)'),
            self::cell('G'.$headerRow, 2, 'Amount (PHP)'),
            self::cell('H'.$headerRow, 2, 'Remarks'),
        ], 30);

        $peso = fn (float $amount) => '₱ '.number_format($amount, 2);

        foreach ($items->values() as $index => $purchaseOrderItem) {
            $itemRow = $headerRow + $index + 1;
            $itemName = $purchaseOrderItem->item?->name ?? 'Item';
            $unit = $purchaseOrderItem->item?->unit ?? '';
            $minShelf = (int) ($purchaseOrderItem->min_remaining_months ?? 0);
            $requirement = $minShelf > 0 ? '≥ '.$minShelf.' months' : 'Non-perishable';
            $extended = $purchaseOrderItem->qty * (float) $purchaseOrderItem->unit_price;

            $rows[] = self::row($itemRow, [
                self::cell('A'.$itemRow, 4, (string) ($index + 1)),
                self::cell('B'.$itemRow, 3, $itemName),
                self::cell('C'.$itemRow, 4, $unit !== '' ? $unit : null),
                self::cell('D'.$itemRow, 4, (string) $purchaseOrderItem->qty),
                self::cell('E'.$itemRow, 4, $requirement),
                self::cell('F'.$itemRow, 4, $peso((float) $purchaseOrderItem->unit_price)),
                self::cell('G'.$itemRow, 4, $peso($extended)),
                self::cell('H'.$itemRow, 4, null),
            ], 18);
        }

        $totalRow = $headerRow + $items->count() + 1;
        $subtotal = (float) $items->sum(fn (PurchaseOrderItem $purchaseOrderItem) => $purchaseOrderItem->qty * (float) $purchaseOrderItem->unit_price);
        $freight = (float) $purchaseOrder->delivery_fee;
        $total = $subtotal + $freight;

        $rows[] = self::row($totalRow, [
            self::cell('A'.$totalRow, 15, 'Subtotal'),
            self::cell('B'.$totalRow, 15, null),
            self::cell('C'.$totalRow, 15, null),
            self::cell('D'.$totalRow, 15, null),
            self::cell('E'.$totalRow, 15, null),
            self::cell('F'.$totalRow, 15, null),
            self::cell('G'.$totalRow, 10, $peso($subtotal)),
            self::cell('H'.$totalRow, 10, null),
        ], 18);

        $deliveryRow = $totalRow + 1;
        $rows[] = self::row($deliveryRow, [
            self::cell('A'.$deliveryRow, 15, 'Delivery'),
            self::cell('B'.$deliveryRow, 15, null),
            self::cell('C'.$deliveryRow, 15, null),
            self::cell('D'.$deliveryRow, 15, null),
            self::cell('E'.$deliveryRow, 15, null),
            self::cell('F'.$deliveryRow, 15, null),
            self::cell('G'.$deliveryRow, 10, $peso($freight)),
            self::cell('H'.$deliveryRow, 10, null),
        ], 18);

        $grandTotalRow = $deliveryRow + 1;
        $rows[] = self::row($grandTotalRow, [
            self::cell('A'.$grandTotalRow, 13, 'TOTAL'),
            self::cell('B'.$grandTotalRow, 13, null),
            self::cell('C'.$grandTotalRow, 13, null),
            self::cell('D'.$grandTotalRow, 13, null),
            self::cell('E'.$grandTotalRow, 13, null),
            self::cell('F'.$grandTotalRow, 13, null),
            self::cell('G'.$grandTotalRow, 12, $peso($total)),
            self::cell('H'.$grandTotalRow, 12, null),
        ], 24);

        $rowNumber = $grandTotalRow + 1;

        $rows[] = self::styledRow($rowNumber++, 20);

        $termsRow = $rowNumber++;
        $rows[] = self::row($termsRow, [
            self::cell('A'.$termsRow, 18, 'TERMS AND CONDITIONS'),
            self::cell('B'.$termsRow, 19, null),
            self::cell('C'.$termsRow, 19, null),
            self::cell('D'.$termsRow, 19, null),
            self::cell('E'.$termsRow, 19, null),
            self::cell('F'.$termsRow, 19, null),
            self::cell('G'.$termsRow, 19, null),
            self::cell('H'.$termsRow, 19, null),
        ]);

        $termRows = [];
        foreach (self::TERMS as $term) {
            $termRow = $rowNumber++;
            $termRows[] = $termRow;
            $rows[] = self::row($termRow, [
                self::cell('A'.$termRow, 19, $term),
                self::cell('B'.$termRow, 19, null),
                self::cell('C'.$termRow, 19, null),
                self::cell('D'.$termRow, 19, null),
                self::cell('E'.$termRow, 19, null),
                self::cell('F'.$termRow, 19, null),
                self::cell('G'.$termRow, 19, null),
                self::cell('H'.$termRow, 19, null),
            ]);
        }

        $rows[] = self::styledRow($rowNumber++, 20);

        $signatureRow = $rowNumber++;
        $rows[] = self::row($signatureRow, [
            self::cell('A'.$signatureRow, 21, 'Prepared by: ____________________'),
            self::cell('B'.$signatureRow, 21, null),
            self::cell('C'.$signatureRow, 21, null),
            self::cell('D'.$signatureRow, 21, 'Checked by: ____________________'),
            self::cell('E'.$signatureRow, 21, null),
            self::cell('F'.$signatureRow, 21, null),
            self::cell('G'.$signatureRow, 21, 'Approved by: ____________________'),
            self::cell('H'.$signatureRow, 21, null),
        ]);

        $rows[] = self::styledRow($rowNumber++, 20);

        $ackRow = $rowNumber++;
        $rows[] = self::row($ackRow, [
            self::cell('A'.$ackRow, 18, 'SUPPLIER ACKNOWLEDGMENT'),
            self::cell('B'.$ackRow, 19, null),
            self::cell('C'.$ackRow, 19, null),
            self::cell('D'.$ackRow, 19, null),
            self::cell('E'.$ackRow, 19, null),
            self::cell('F'.$ackRow, 19, null),
            self::cell('G'.$ackRow, 19, null),
            self::cell('H'.$ackRow, 19, null),
        ]);

        $ackTextRow = $rowNumber++;
        $rows[] = self::row($ackTextRow, [
            self::cell('A'.$ackTextRow, 19, self::ACKNOWLEDGMENT_TEXT),
            self::cell('B'.$ackTextRow, 19, null),
            self::cell('C'.$ackTextRow, 19, null),
            self::cell('D'.$ackTextRow, 19, null),
            self::cell('E'.$ackTextRow, 19, null),
            self::cell('F'.$ackTextRow, 19, null),
            self::cell('G'.$ackTextRow, 19, null),
            self::cell('H'.$ackTextRow, 19, null),
        ]);

        $rows[] = self::styledRow($rowNumber++, 20);

        $finalRow = $rowNumber++;
        $rows[] = self::row($finalRow, [
            self::cell('A'.$finalRow, 19, 'Supplier Name: ____________________'),
            self::cell('B'.$finalRow, 19, null),
            self::cell('C'.$finalRow, 19, null),
            self::cell('D'.$finalRow, 19, null),
            self::cell('E'.$finalRow, 19, 'Authorized Signature: ____________________'),
            self::cell('F'.$finalRow, 19, null),
            self::cell('G'.$finalRow, 19, null),
            self::cell('H'.$finalRow, 19, null),
        ]);

        $merges = [
            'A1:H1',
            'A2:H2',
            'A3:H3',
            'A4:H4',
            'A5:H5',
            'A6:H6',
            'A7:H7',
            'A'.$totalRow.':F'.$totalRow,
            'A'.$deliveryRow.':F'.$deliveryRow,
            'A'.$grandTotalRow.':F'.$grandTotalRow,
            'A'.$termsRow.':H'.$termsRow,
        ];

        foreach ($termRows as $termRow) {
            $merges[] = 'A'.$termRow.':H'.$termRow;
        }

        $merges[] = 'A'.$signatureRow.':C'.$signatureRow;
        $merges[] = 'D'.$signatureRow.':F'.$signatureRow;
        $merges[] = 'G'.$signatureRow.':H'.$signatureRow;
        $merges[] = 'A'.$ackRow.':H'.$ackRow;
        $merges[] = 'A'.$ackTextRow.':H'.$ackTextRow;
        $merges[] = 'A'.$finalRow.':D'.$finalRow;
        $merges[] = 'E'.$finalRow.':H'.$finalRow;

        $mergeCells = '<mergeCells count="'.count($merges).'">';
        foreach ($merges as $merge) {
            $mergeCells .= '<mergeCell ref="'.$merge.'"/>';
        }
        $mergeCells .= '</mergeCells>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x14ac" xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac">'
            .'<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            .'<dimension ref="A1:H'.$finalRow.'"/>'
            .'<sheetViews><sheetView tabSelected="1" view="pageLayout" zoomScaleNormal="100" workbookViewId="0"><selection activeCell="A6" sqref="A6:H6"/></sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15" x14ac:dyDescent="0.25"/>'
            .'<cols>'
            .'<col min="1" max="1" width="17" customWidth="1"/>'
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
            .'<pageSetup scale="69" orientation="landscape" r:id="rId1"/>'
            .'<drawing r:id="rId2"/>'
            .'</worksheet>';
    }

    /**
     * @param  array<int, string>  $cells
     */
    private static function row(int $number, array $cells, ?float $height = null): string
    {
        $attributes = 'r="'.$number.'" spans="1:8" x14ac:dyDescent="0.25"';

        if ($height !== null) {
            $attributes .= ' ht="'.self::number($height).'" customHeight="1"';
        }

        return '<row '.$attributes.'>'
            .implode('', $cells)
            .'</row>';
    }

    private static function styledRow(int $number, int $rowStyle): string
    {
        return '<row r="'.$number.'" spans="1:8" s="'.$rowStyle.'"/>';
    }

    /**
     * @param  array<int, string>  $cells
     */
    private static function customFormatRow(int $number, array $cells, float $height, int $rowStyle = 5): string
    {
        return '<row r="'.$number.'" spans="1:8" s="'.$rowStyle.'" customFormat="1" ht="'.self::number($height).'" customHeight="1" x14ac:dyDescent="0.25">'
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
