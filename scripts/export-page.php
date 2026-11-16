<?php
/** Local read-only WordPress export: wp --require=.../scripts/export-page.php bc-export <local-id> <key> */
if (PHP_SAPI !== 'cli' || !defined('WP_CLI') || WP_CLI !== true) { http_response_code(403); exit; }
require_once __DIR__ . '/lib/page-content.php';
WP_CLI::add_command('bc-export', function (array $args, array $assoc): void {
    try {
        if (count($args) !== 2 || $assoc || !ctype_digit($args[0]) || !preg_match('/^[a-z][a-z0-9-]{0,79}$/D', $args[1])) {
            throw new RuntimeException('Usage: wp bc-export <local-page-id> <stable-key>');
        }
        foreach (['home', 'siteurl'] as $option) {
            if (!in_array(wp_parse_url(get_option($option), PHP_URL_HOST), ['localhost', '127.0.0.1', '::1', '[::1]'], true)) {
                throw new RuntimeException('Exports are allowed only from local WordPress.');
            }
        }
        $page = get_post((int) $args[0]);
        if (!$page || $page->post_type !== 'page' || in_array($page->post_status, ['trash', 'auto-draft'], true)) { throw new RuntimeException('Local page not found.'); }
        $staging = bcal_staging_content($page->post_content);
        bcal_validate_content($staging);
        $directory = dirname(__DIR__) . '/content/pages';
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) { throw new RuntimeException('Cannot create content/pages.'); }
        $export = ['format_version' => 1, 'post_type' => 'page', 'title' => $page->post_title, 'slug' => $page->post_name, 'content' => $page->post_content];
        foreach ([$args[1] . '.json' => $export, $args[1] . '-staging.json' => $staging] as $file => $value) {
            $json = bcal_json_encode($value);
            if (file_put_contents($directory . '/' . $file, $json) !== strlen($json)) { throw new RuntimeException('Incomplete export: ' . $file); }
        }
        WP_CLI::success('Exported content/pages/' . $args[1] . '{,-staging}.json. No database or SEO metadata changes.');
    } catch (Throwable $error) { WP_CLI::error($error->getMessage()); }
}, ['synopsis' => '<local-id> <key>']);
