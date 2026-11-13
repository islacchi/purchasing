<?php

namespace App\Support;

use App\Models\Project;
use ZipArchive;

/**
 * Builds a standalone .xlsx "project summary" workbook from scratch.
 *
 * The layout mirrors the on-screen project summary sheet: title block,
 * two-column project details, notes (when present), and the item table.
 * Page setup is Folio (8.5 × 13 in), landscape, fit-to-width, opened in
 * the normal grid view — Page Layout view renders empty in viewers that
 * do not recognise the Folio paper size.
 *
 * Standalone means no template file is required: the parts are assembled
 * here with inline strings and a small styles table.
 */
class ProjectSummaryWorkbook
{
    private const LAST_COL = 'N';
    private const COLUMN_COUNT = 14;

    /**
     * @return string Raw .xlsx contents (binary zip)
     */
    public static function build(Project $project): string
    {
        $project->loadMissing(['procuringEntity', 'items.item']);

        $modeLabels = [
            'shopping' => 'Shopping',
            'small_value' => 'Small value',
            'competitive_bidding' => 'Competitive bidding',
            'direct_contracting' => 'Direct contracting',
        ];

        $modeLabel = $modeLabels[$project->mode_of_procurement]
            ?? ucfirst(str_replace('_', ' ', (string) $project->mode_of_procurement));

        $deliveryPeriod = $project->delivery_days !== null
            ? $project->delivery_days.' days'
            : '—';

        if ($project->delivery_due_date !== null) {
            $deliveryPeriod .= ' (due '.$project->delivery_due_date->format('M j, Y').')';
        }

        $totalItems = $project->totalItemCount();
        $fulfilledItems = $project->fulfilledItemCount();

        $progressLabel = $totalItems > 0
            ? $fulfilledItems.' / '.$totalItems.' items fulfilled'
            : 'No items recorded yet';

        $amountAwarded = $project->amount_awarded !== null
            ? self::peso((float) $project->amount_awarded)
            : '—';

        $dateAwarded = $project->date_awarded?->format('F j, Y') ?? '—';

        $status = $project->statusLabel();
        $statusStyle = match ($project->statusColor()) {
            'green' => 5,
            'blue' => 6,
            default => 7,
        };

        $rows = [];
        $merges = [];
        $rowNumber = 1;

        $rows[] = self::row($rowNumber++, [self::cell('A1', 1, 'PROJECT SUMMARY')], 17, self::merge($merges, 'A1:N1'));
        $rows[] = self::row($rowNumber++, [self::cell('A2', 2, $project->title)], 24, self::merge($merges, 'A2:N2'));
        $rows[] = self::row($rowNumber++, [self::cell('A3', 3, 'Reference no. '.$project->reference_no)], 14, self::merge($merges, 'A3:N3'));
        $rows[] = self::row($rowNumber++, [self::cell('A4', $statusStyle, $status)], 18, self::merge($merges, 'A4:N4'));
        $rows[] = self::blankRow($rowNumber++, 6);

        $rows[] = self::infoRow($rowNumber++, 'Entity', $project->procuringEntity?->name ?? '—', 'Mode', $modeLabel, $merges);
        $rows[] = self::infoRow($rowNumber++, 'Amount awarded', $amountAwarded, 'Date awarded', $dateAwarded, $merges);
        $rows[] = self::infoRow($rowNumber++, 'Delivery period', $deliveryPeriod, 'Fulfillment', $progressLabel, $merges);

        $notes = trim((string) $project->notes);

        if ($notes !== '') {
            $notesLabelRow = $rowNumber++;
            $rows[] = self::row($notesLabelRow, [
                self::cell('A'.$notesLabelRow, 8, 'NOTES'),
                self::blankCells('B'.$notesLabelRow, 'N'.$notesLabelRow, 9),
            ], 14, self::merge($merges, 'A'.$notesLabelRow.':N'.$notesLabelRow));

            $lines = (int) max(1, ceil(mb_strlen($notes) / 105));
            $notesRow = $rowNumber++;
            $rows[] = self::row($notesRow, [
                self::cell('A'.$notesRow, 10, $notes),
                self::blankCells('B'.$notesRow, 'N'.$notesRow, 9),
            ], max(26, $lines * 15 + 8), self::merge($merges, 'A'.$notesRow.':N'.$notesRow));
        }

        $rows[] = self::blankRow($rowNumber++, 6);

        $headerRow = $rowNumber++;
        $headerCells = [
            self::cell('A'.$headerRow, 11, 'Item No.'),
            self::cell('B'.$headerRow, 11, 'DESCRIPTION'),
            self::cell('C'.$headerRow, 11, 'Qty.'),
            self::cell('D'.$headerRow, 11, 'Unit'),
            self::cell('E'.$headerRow, 11, 'RQ'),
            self::cell('F'.$headerRow, 11, 'Brand for Unit Cost Basis'),
            self::cell('G'.$headerRow, 11, 'Brand'),
            self::cell('H'.$headerRow, 11, 'Lot #'),
            self::cell('I'.$headerRow, 11, 'Exp. Date'),
            self::cell('J'.$headerRow, 11, 'UNIT COST'),
            self::cell('K'.$headerRow, 11, 'UNIT PRICE'),
            self::cell('L'.$headerRow, 11, 'TOTAL UNIT PRICE'),
            self::cell('M'.$headerRow, 11, 'COMPLETE/LACKING(S)'),
            self::cell('N'.$headerRow, 11, 'BOX NO.'),
        ];
        $rows[] = self::row($headerRow, $headerCells, 30);

        if ($project->items->isEmpty()) {
            $emptyRow = $rowNumber++;
            $rows[] = self::row($emptyRow, [
                self::cell('A'.$emptyRow, 14, 'No items recorded for this project.'),
                self::blankCells('B'.$emptyRow, 'N'.$emptyRow, 12),
            ], 24, self::merge($merges, 'A'.$emptyRow.':N'.$emptyRow));
        } else {
            foreach ($project->items->values() as $index => $projectItem) {
                $itemRow = $rowNumber++;
                $unitPrice = (float) ($projectItem->awarded_unit_price ?? $projectItem->unit_cost);
                $remaining = $projectItem->remainingQuantity();
                $lineTotal = $remaining * $unitPrice;

                $unitPriceCell = $projectItem->awarded_unit_price !== null
                    ? self::numberCell('K'.$itemRow, 17, (float) $projectItem->awarded_unit_price)
                    : self::cell('K'.$itemRow, 23, '—');

                $rows[] = self::row($itemRow, [
                    self::cell('A'.$itemRow, 13, (string) ($index + 1)),
                    self::cell('B'.$itemRow, 14, $projectItem->item?->name ?? '—'),
                    self::numberCell('C'.$itemRow, 15, $remaining),
                    self::cell('D'.$itemRow, 16, $projectItem->item?->unit ?? '—'),
                    // Deliberately blank: not sourced from the database.
                    self::cell('E'.$itemRow, 13, null),
                    self::cell('F'.$itemRow, 12, null),
                    self::cell('G'.$itemRow, 12, null),
                    self::cell('H'.$itemRow, 12, null),
                    self::cell('I'.$itemRow, 12, null),
                    self::numberCell('J'.$itemRow, 17, (float) $projectItem->unit_cost),
                    $unitPriceCell,
                    self::numberCell('L'.$itemRow, 17, $lineTotal),
                    // Deliberately blank: not sourced from the database.
                    self::cell('M'.$itemRow, 12, null),
                    self::cell('N'.$itemRow, 13, null),
                ], 26);
            }
        }

        $rows[] = self::blankRow($rowNumber++, 10);

        $footerRow = $rowNumber++;
        $rows[] = self::row($footerRow, [self::cell('A'.$footerRow, 18, 'Generated '.now()->format('F j, Y g:i A').' · Purchasing')], 16, self::merge($merges, 'A'.$footerRow.':N'.$footerRow));

        $mergeCells = '<mergeCells count="'.count($merges).'">';
        foreach ($merges as $merge) {
            $mergeCells .= '<mergeCell ref="'.$merge.'"/>';
        }
        $mergeCells .= '</mergeCells>';

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            .'<dimension ref="A1:'.self::LAST_COL.$footerRow.'"/>'
            .'<sheetViews><sheetView tabSelected="1" view="normal" zoomScaleNormal="100" workbookViewId="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<cols>'
            .'<col min="1" max="1" width="7" customWidth="1"/>'
            .'<col min="2" max="2" width="44" customWidth="1"/>'
            .'<col min="3" max="3" width="9" customWidth="1"/>'
            .'<col min="4" max="4" width="9" customWidth="1"/>'
            .'<col min="5" max="5" width="6" customWidth="1"/>'
            .'<col min="6" max="6" width="15" customWidth="1"/>'
            .'<col min="7" max="7" width="13" customWidth="1"/>'
            .'<col min="8" max="8" width="11" customWidth="1"/>'
            .'<col min="9" max="9" width="11" customWidth="1"/>'
            .'<col min="10" max="10" width="13.5" customWidth="1"/>'
            .'<col min="11" max="11" width="13.5" customWidth="1"/>'
            .'<col min="12" max="12" width="16" customWidth="1"/>'
            .'<col min="13" max="13" width="17" customWidth="1"/>'
            .'<col min="14" max="14" width="10.5" customWidth="1"/>'
            .'</cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .$mergeCells
            .'<pageMargins left="0.35" right="0.35" top="0.3" bottom="0.3" header="0.15" footer="0.15"/>'
            .'<pageSetup paperSize="14" scale="100" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            .'</worksheet>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<bookViews><workbookView/></bookViews>'
            .'<sheets><sheet name="Project Summary" sheetId="1" r:id="rId1"/></sheets>'
            .'<definedNames><definedName name="_xlnm.Print_Area" localSheetId="0">\'Project Summary\'!$A$1:$'.self::LAST_COL.'$'.$footerRow.'</definedName></definedNames>'
            .'</workbook>';

        $generated = now()->utc();

        return self::zip([
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
                .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
                .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
                .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
                .'</Relationships>',
            'xl/workbook.xml' => $workbook,
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                .'</Relationships>',
            'xl/worksheets/sheet1.xml' => $sheet,
            'xl/styles.xml' => self::stylesXml(),
            'docProps/core.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
                .'xmlns:dc="http://purl.org/dc/elements/1.1/" '
                .'xmlns:dcterms="http://purl.org/dc/terms/" '
                .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                .'<dc:creator>Purchasing</dc:creator>'
                .'<cp:lastModifiedBy>Purchasing</cp:lastModifiedBy>'
                .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$generated->format('Y-m-d\TH:i:s\Z').'</dcterms:created>'
                .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$generated->format('Y-m-d\TH:i:s\Z').'</dcterms:modified>'
                .'</cp:coreProperties>',
            'docProps/app.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
                .'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
                .'<Application>Purchasing</Application>'
                .'<DocSecurity>0</DocSecurity>'
                .'<ScaleCrop>false</ScaleCrop>'
                .'<Company>Purchasing</Company>'
                .'<LinksUpToDate>false</LinksUpToDate>'
                .'<SharedDoc>false</SharedDoc>'
                .'<HyperlinksChanged>false</HyperlinksChanged>'
                .'<AppVersion>1.0</AppVersion>'
                .'</Properties>',
        ]);
    }

    /**
     * Two label/value pairs on one row (label left, value right, thin bottom border).
     *
     * @param  array<int, string>  $merges
     */
    private static function infoRow(int $rowNumber, string $labelA, string $valueA, string $labelB, string $valueB, array &$merges): string
    {
        $merges[] = 'A'.$rowNumber.':C'.$rowNumber;
        $merges[] = 'D'.$rowNumber.':H'.$rowNumber;
        $merges[] = 'I'.$rowNumber.':K'.$rowNumber;
        $merges[] = 'L'.$rowNumber.':N'.$rowNumber;

        return self::row($rowNumber, [
            self::cell('A'.$rowNumber, 19, $labelA),
            self::blankCells('B'.$rowNumber, 'C'.$rowNumber, 20),
            self::cell('D'.$rowNumber, 21, $valueA),
            self::blankCells('E'.$rowNumber, 'H'.$rowNumber, 22),
            self::cell('I'.$rowNumber, 19, $labelB),
            self::blankCells('J'.$rowNumber, 'K'.$rowNumber, 20),
            self::cell('L'.$rowNumber, 21, $valueB),
            self::blankCells('M'.$rowNumber, 'N'.$rowNumber, 22),
        ], 18);
    }

    /**
     * @param  array<int, string>  $merges
     */
    private static function merge(array &$merges, string $reference): string
    {
        $merges[] = $reference;

        return '';
    }

    /**
     * @param  array<int, string>  $cells
     */
    private static function row(int $number, array $cells, float $height, string $ignored = ''): string
    {
        return '<row r="'.$number.'" ht="'.self::number($height).'" customHeight="1">'.implode('', $cells).'</row>';
    }

    private static function blankRow(int $number, float $height): string
    {
        return '<row r="'.$number.'" ht="'.self::number($height).'" customHeight="1"/>';
    }

    private static function blankCells(string $fromRef, string $toRef, int $style): string
    {
        [$fromCol, $fromRow] = self::splitReference($fromRef);
        [$toCol, $toRow] = self::splitReference($toRef);

        $out = '';

        for ($index = self::columnIndex($fromCol); $index <= self::columnIndex($toCol); $index++) {
            $out .= '<c r="'.self::columnName($index).$fromRow.'" s="'.$style.'"/>';
        }

        return $out;
    }

    /**
     * @return array{0: string, 1: int}
     */
    private static function splitReference(string $reference): array
    {
        if (! preg_match('/^([A-Z]+)(\d+)$/', $reference, $matches)) {
            throw new \InvalidArgumentException('Invalid cell reference: '.$reference);
        }

        return [$matches[1], (int) $matches[2]];
    }

    private static function columnIndex(string $column): int
    {
        $index = 0;

        for ($i = 0, $length = strlen($column); $i < $length; $i++) {
            $index = $index * 26 + (ord($column[$i]) - 64);
        }

        return $index;
    }

    private static function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $name = chr(65 + $remainder).$name;
            $index = intdiv($index - 1, 26);
        }

        return $name;
    }

    private static function cell(string $reference, int $style, ?string $value): string
    {
        if ($value === null) {
            return '<c r="'.$reference.'" s="'.$style.'"/>';
        }

        return '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.self::escape($value).'</t></is></c>';
    }

    private static function numberCell(string $reference, int $style, float $value): string
    {
        return '<c r="'.$reference.'" s="'.$style.'"><v>'.self::number($value).'</v></c>';
    }

    private static function peso(float $value): string
    {
        return '₱ '.number_format($value, 2);
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(sprintf('%.4f', $value), '0'), '.');
    }

    private static function stylesXml(): string
    {
        $fonts = [
            '<font><sz val="11"/><color theme="1"/><name val="Calibri"/></font>', // 0
            '<font><b/><sz val="11"/><color rgb="FF111827"/><name val="Calibri"/></font>', // 1
            '<font><b/><sz val="16"/><color rgb="FF111827"/><name val="Calibri"/></font>', // 2 title
            '<font><b/><sz val="8"/><color rgb="FF6B7280"/><name val="Calibri"/></font>', // 3 eyebrow
            '<font><sz val="9"/><color rgb="FF6B7280"/><name val="Calibri"/></font>', // 4 muted small
            '<font><b/><sz val="9"/><color rgb="FF15803D"/><name val="Calibri"/></font>', // 5 green pill text
            '<font><b/><sz val="9"/><color rgb="FF1D4ED8"/><name val="Calibri"/></font>', // 6 blue pill text
            '<font><b/><sz val="9"/><color rgb="FF4B5563"/><name val="Calibri"/></font>', // 7 gray pill text
            '<font><b/><sz val="8"/><color rgb="FF6B7280"/><name val="Calibri"/></font>', // 8 notes eyebrow
            '<font><sz val="10"/><color rgb="FF374151"/><name val="Calibri"/></font>', // 9 notes box fill font
            '<font><sz val="10"/><color rgb="FF374151"/><name val="Calibri"/></font>', // 10 notes text
            '<font><b/><sz val="10"/><color rgb="FF374151"/><name val="Calibri"/></font>', // 11 table header
            '<font><i/><sz val="8"/><color rgb="FF9CA3AF"/><name val="Calibri"/></font>', // 12 footer italic
            '<font><sz val="11"/><color rgb="FF111827"/><name val="Calibri"/></font>', // 13 item row text
        ];

        $fills = [
            '<fill><patternFill patternType="none"/></fill>', // 0
            '<fill><patternFill patternType="gray125"/></fill>', // 1
            '<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>', // 2
            '<fill><patternFill patternType="solid"><fgColor rgb="FFF3F4F6"/><bgColor indexed="64"/></patternFill></fill>', // 3 gray-100
            '<fill><patternFill patternType="solid"><fgColor rgb="FFF9FAFB"/><bgColor indexed="64"/></patternFill></fill>', // 4 notes box
            '<fill><patternFill patternType="solid"><fgColor rgb="FFDCFCE7"/><bgColor indexed="64"/></patternFill></fill>', // 5 green pill
            '<fill><patternFill patternType="solid"><fgColor rgb="FFDBEAFE"/><bgColor indexed="64"/></patternFill></fill>', // 6 blue pill
            '<fill><patternFill patternType="solid"><fgColor rgb="FFF3F4F6"/><bgColor indexed="64"/></patternFill></fill>', // 7 gray pill
        ];

        $borders = [
            '<border><left/><right/><top/><bottom/><diagonal/></border>', // 0
            '<border><left/><right/><top/><bottom style="thin"><color rgb="FFE5E7EB"/></bottom><diagonal/></border>', // 1 bottom-only
            '<border><left style="thin"><color rgb="FFD1D5DB"/></left><right style="thin"><color rgb="FFD1D5DB"/></right><top style="thin"><color rgb="FFD1D5DB"/></top><bottom style="thin"><color rgb="FFD1D5DB"/></bottom><diagonal/></border>', // 2 box
        ];

        $xfs = [
            0 => '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>', // 0
            1 => '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment horizontal="left" vertical="center"/></xf>', // 1 eyebrow
            2 => '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>', // 2 title
            3 => '<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment horizontal="left" vertical="center"/></xf>', // 3 ref line
            4 => '<xf numFmtId="0" fontId="7" fillId="7" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="left" vertical="center"/></xf>', // 4 (unused)
            5 => '<xf numFmtId="0" fontId="5" fillId="5" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="left" vertical="center"/></xf>', // 5 green pill
            6 => '<xf numFmtId="0" fontId="6" fillId="6" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="left" vertical="center"/></xf>', // 6 blue pill
            7 => '<xf numFmtId="0" fontId="7" fillId="7" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="left" vertical="center"/></xf>', // 7 gray pill
            8 => '<xf numFmtId="0" fontId="8" fillId="4" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>', // 8 notes eyebrow (boxed)
            9 => '<xf numFmtId="0" fontId="9" fillId="4" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>', // 9 notes box fill
            10 => '<xf numFmtId="0" fontId="10" fillId="4" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>', // 10 notes text
            11 => '<xf numFmtId="0" fontId="11" fillId="3" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>', // 11 table header
            12 => '<xf numFmtId="0" fontId="13" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>', // 12 bordered center
            13 => '<xf numFmtId="0" fontId="13" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>', // 13 item no. / bordered center
            14 => '<xf numFmtId="0" fontId="13" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>', // 14 description wrap
            15 => '<xf numFmtId="1" fontId="13" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1" applyNumberFormat="1"><alignment horizontal="right" vertical="center"/></xf>', // 15 integer right
            16 => '<xf numFmtId="0" fontId="13" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>', // 16 unit left
            17 => '<xf numFmtId="164" fontId="13" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1" applyNumberFormat="1"><alignment horizontal="right" vertical="center"/></xf>', // 17 peso
            18 => '<xf numFmtId="0" fontId="12" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment horizontal="left" vertical="center"/></xf>', // 18 footer italic
            19 => '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>', // 19 info label
            20 => '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>', // 20 info label spacer
            21 => '<xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="right" vertical="center" wrapText="1"/></xf>', // 21 info value
            22 => '<xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>', // 22 info value spacer
            23 => '<xf numFmtId="0" fontId="13" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>', // 23 right-aligned text
        ];

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;₱&quot;#,##0.00"/></numFmts>'
            .'<fonts count="'.count($fonts).'">'.implode('', $fonts).'</fonts>'
            .'<fills count="'.count($fills).'">'.implode('', $fills).'</fills>'
            .'<borders count="'.count($borders).'">'.implode('', $borders).'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="'.count($xfs).'">'.implode('', $xfs).'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
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
