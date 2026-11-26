<?php

/**
 * Minimal Markdown → PDF converter used to produce the two documentation
 * PDFs (Manual and Documentation) in this repository.
 *
 * Usage: php docs/create_pdfs.php
 */

declare(strict_types=1);

function pdf_text_lines(string $markdown): array
{
    $lines = [];

    foreach (preg_split('/\r?\n/', $markdown) as $raw) {
        $line = rtrim($raw);
        $line = preg_replace('/^#{1,6}\s+/', '', $line); // headings
        $line = preg_replace('/^\s*[-*+]\s+/', '• ', $line); // bullets
        $line = preg_replace('/\*\*|__/', '', $line); // bold
        $line = preg_replace('/[\x{FFFD}]/u', '-', $line); // replace broken glyphs

        // Transliterate to latin-1 (Helvetica) as best we can.
        if (function_exists('iconv')) {
            $line = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $line) ?: $line;
        }

        $lines[] = trim($line);
    }

    return array_values(array_filter($lines, fn ($l) => $l !== ''));
}

function pdf_escape(string $value): string
{
    return str_replace(["\\", '(', ')', "\r"], ['\\\\', '\\(', '\\)', '\\r'], $value);
}

function make_pdf(string $markdown, string $outPath): void
{
    $lines = pdf_text_lines($markdown);
    $chunks = array_chunk($lines, 48);
    $chunks = $chunks === [] ? [['']] : $chunks;

    $pageObjIds = [];
    $contentObjIds = [];
    $next = 4;
    foreach ($chunks as $i => $_) {
        $pageObjIds[$i] = $next++;
        $contentObjIds[$i] = $next++;
    }

    $objects = [
        1 => '<< /Type /Catalog /Pages 2 0 R >>',
        2 => '<< /Type /Pages /Kids ['.implode(' ', array_map(fn ($id) => $id.' 0 R', $pageObjIds)).'] /Count '.count($chunks).' >>',
        3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    ];

    foreach ($chunks as $i => $chunk) {
        $ops = ['BT /F1 10 Tf 12 TL', '50 780 Td'];
        foreach ($chunk as $line) {
            $ops[] = '('.pdf_escape($line).') Tj T*';
        }
        $ops[] = 'ET';
        $stream = implode("\n", $ops);

        $objects[$pageObjIds[$i]] = sprintf(
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents %d 0 R >>',
            $contentObjIds[$i],
        );
        $objects[$contentObjIds[$i]] = "<< /Length ".strlen($stream)." >>\nstream\n".$stream."\nendstream";
    }

    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $id => $body) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id." 0 obj\n".$body."\nendobj\n";
    }

    $xrefStart = strlen($pdf);
    $count = count($objects) + 1;
    $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
    for ($id = 1; $id <= count($objects); $id++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
    }
    $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";

    file_put_contents($outPath, $pdf);
    echo 'wrote '.$outPath.' ('.strlen($pdf).' bytes, '.count($chunks).' page(s))'.PHP_EOL;
}

$root = dirname(__DIR__);
$manual = file_get_contents($root.'/docs/Teaching-Guide-Purchasing-System.md');
$docs = file_get_contents($root.'/docs/RUNBOOK.md');

make_pdf($manual, $root.'/docs/Manual.pdf');
make_pdf($docs, $root.'/docs/Documentation.pdf');
