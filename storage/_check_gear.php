<?php
// Temporary helper: validate the Settings gear SVG inside layouts/app.blade.php is well-formed XML.
$file = __DIR__ . '/../resources/views/layouts/app.blade.php';
$raw = file_get_contents($file);

if (!preg_match('/<svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">\s*(<circle[^>]*\/>\s*<path d="M19\.4[^>]*\/>)\s*<\/svg>/s', $raw, $m)) {
    fwrite(STDERR, "Could not isolate the settings gear SVG.\n");
    exit(1);
}
$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">' . $m[1] . '</svg>';

$doc = new DOMDocument();
libxml_use_internal_errors(true);
$ok = $doc->loadXML($svg);
$errors = libxml_get_errors();
libxml_clear_errors();

if (!$ok) {
    fwrite(STDERR, "Settings gear SVG in app.blade.php is NOT valid XML:\n");
    foreach ($errors as $e) { fwrite(STDERR, "  - " . trim($e->message) . "\n"); }
    exit(1);
}
fwrite(STDOUT, "OK: Settings gear SVG in app.blade.php is valid XML.\n");