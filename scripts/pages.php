<?php
/** Register with: wp --skip-plugins --skip-themes --require=/private/release/scripts/pages.php bc-pages ... */
if (PHP_SAPI !== 'cli' || !defined('WP_CLI') || WP_CLI !== true) { http_response_code(403); exit; }
require_once __DIR__ . '/lib/page-deployer.php';
WP_CLI::add_command('bc-pages', new BCAL_Page_Deployer(dirname(__DIR__)), [
    'synopsis' => '[<operation>] [<id>] [--manifest=<path>] [--page=<key>] [--plan=<path>] [--plan-sha256=<sha256>] [--backup=<path>] [--apply]',
]);
