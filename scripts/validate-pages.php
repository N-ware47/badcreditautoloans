<?php
/** Offline only. Optionally supply --wp-core=<local WordPress root> for the real block parser. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/lib/page-content.php';
try {
    $core = null;
    foreach (array_slice($argv, 1) as $argument) {
        if (strpos($argument, '--wp-core=') !== 0) { throw new RuntimeException('Unknown validation argument.'); }
        $core = substr($argument, strlen('--wp-core='));
    }
    if ($core !== null) {
        foreach (['class-wp-block-parser-block.php', 'class-wp-block-parser-frame.php', 'class-wp-block-parser.php'] as $file) {
            require_once $core . '/wp-includes/' . $file;
        }
    }
    $root = dirname(__DIR__);
    $manifest = bcal_manifest($root . '/content/pages-staging.json');
    foreach ($manifest['pages'] as $page) {
        $report = bcal_validate_content(bcal_content_read(bcal_content_path($root, $page['file'])));
        echo bcal_json_encode(['key' => $page['key'], 'adoption_baseline' => $page['baseline_sha256'] === null && $page['existing_id'] !== null ? 'REQUIRES STAGING INSPECTION' : 'configured', 'wordpress_parser' => $core !== null] + $report);
    }
} catch (Throwable $error) { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); }
