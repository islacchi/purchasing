<?php
$file = 'resources/views/Quotation/show.blade.php';
$content = file_get_contents($file);
$content = str_replace(
    "route('purchase-order.create', ['quotation' => $quotation['id']])",
    "route('purchase-order.create', ['rfq' => $quotation['id']])",
    $content
);
file_put_contents($file, $content);
echo "Fixed: " . ($content === file_get_contents($file) ? "Success" : "Failed") . "\n";
echo "Changes applied.\n";
