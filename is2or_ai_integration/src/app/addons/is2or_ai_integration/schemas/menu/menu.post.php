<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

$schema['central']['products']['items']['is2or_ai_integration.risky_listings'] = [
    'href' => 'is2or_ai_integration.risky_listings',
    'position' => 900,
];

$schema['central']['products']['items']['is2or_ai_integration.photo_cleanup_queue'] = [
    'href' => 'is2or_ai_integration.photo_cleanup_queue',
    'position' => 910,
];

$schema['central']['vendors']['items']['is2or_ai_integration.document_checks'] = [
    'href' => 'is2or_ai_integration.document_checks',
    'position' => 920,
];

return $schema;