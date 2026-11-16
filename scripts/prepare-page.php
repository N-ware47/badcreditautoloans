<?php
/** php scripts/prepare-page.php [source.json] [destination.json] */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/lib/page-content.php';
try {
    if ($argc !== 1 && $argc !== 3) {
        throw new RuntimeException('Usage: php scripts/prepare-page.php [source.json destination.json]');
    }
    $root = dirname(__DIR__);
    $source = $argv[1] ?? $root . '/content/home.json';
    $destination = $argv[2] ?? $root . '/content/home-staging.json';
    if (realpath($source) === realpath($destination)) {
        throw new RuntimeException('Keep the original export separate from the staging export.');
    }
    $content = bcal_staging_content(bcal_content_read($source));
    $report = bcal_validate_content($content);
    $json = bcal_json_encode($content); // UTF-8, no BOM; replace URLs after decoding.
    if (file_put_contents($destination, $json) !== strlen($json)) {
        throw new RuntimeException('Could not write the complete staging export.');
    }
    echo bcal_json_encode(['file' => $destination] + $report);
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
