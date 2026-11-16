<?php
/**
 * Read-only compatibility check for the original homepage import.
 * wp --skip-plugins --skip-themes eval-file /private/release/scripts/import-staging-home.php
 * All writes have moved to bc-pages, which requires a reviewed plan and full-state baseline.
 */
if (PHP_SAPI !== 'cli' || !defined('ABSPATH') || !defined('WP_CLI') || WP_CLI !== true) {
    http_response_code(403);
    exit("Run this script using WP-CLI.\n");
}
require_once __DIR__ . '/lib/page-content.php';
try {
    if (!empty($args)) { throw new RuntimeException('This compatibility command is dry-run only and takes no arguments. Use bc-pages for approved writes.'); }
    if (rtrim((string) realpath(ABSPATH), '/') !== '/home/customer/www/staging2.badcreditautoloans.net/public_html'
        || rtrim((string) get_option('home'), '/') !== 'https://staging2.badcreditautoloans.net'
        || rtrim((string) get_option('siteurl'), '/') !== 'https://staging2.badcreditautoloans.net'
        || is_multisite()) {
        throw new RuntimeException('STOP: Incorrect WordPress environment.');
    }
    $page = get_post(21);
    if (!$page || $page->post_type !== 'page' || get_option('show_on_front') !== 'page' || (int) get_option('page_on_front') !== 21) {
        throw new RuntimeException('STOP: Page 21 is not the configured staging homepage.');
    }
    $private_home = getenv('HOME');
    if (!$private_home || $private_home[0] !== '/') { throw new RuntimeException('STOP: Missing absolute HOME directory.'); }
    $baseline_file = $private_home . '/staging-page-backups/home-baseline.sha256';
    if (!is_file($baseline_file)) { throw new RuntimeException('STOP: Existing homepage baseline is missing.'); }
    $expected = trim(file_get_contents($baseline_file));
    if (!preg_match('/^[a-f0-9]{64}$/D', $expected) || !hash_equals($expected, hash('sha256', $page->post_content))) {
        throw new RuntimeException('STOP: Homepage changed since the original baseline.');
    }
    $report = bcal_validate_content(bcal_content_read(dirname(__DIR__) . '/content/home-staging.json'));
    WP_CLI::log(bcal_json_encode(['environment' => 'staging2', 'page_id' => 21, 'legacy_content_baseline' => 'MATCH', 'mode' => 'DRY RUN'] + $report));
    WP_CLI::success('Dry-run passed. No changes made. Adopt the full-state baseline with bc-pages before applying.');
} catch (Throwable $error) { WP_CLI::error($error->getMessage()); }
