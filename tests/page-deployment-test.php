<?php
/** Offline safety tests. No wp-load.php, database connection, SSH or apply operation. */
require_once dirname(__DIR__) . '/scripts/lib/page-deployer.php';
define('ARRAY_A', 'ARRAY_A');
define('WP_CLI', true);
define('ABSPATH', '/production/wordpress/');
class WP_CLI {
    public static function error($message) { throw new RuntimeException($message); }
    public static function log($message) {}
    public static function success($message) {}
}
class WP_Block_Type_Registry {
    public static function get_instance() { return new self(); }
    public function is_registered($name) { return strpos($name, 'core/') === 0; }
}
function get_current_user_id() { return 7; }
function current_user_can(...$args) { return true; }
class BCAL_Read_Only_DB {
    public string $posts = 'test_posts';
    public string $postmeta = 'test_postmeta';
    public string $options = 'test_options';
    public string $last_error = '';
    public array $rows = [];
    public array $meta = [];
    public function prepare($sql, ...$args) {
        foreach ($args as $value) {
            $sql = preg_replace('/%[ds]/', is_int($value) ? (string) $value : "'" . $value . "'", $sql, 1);
        }
        return $sql;
    }
    public function query($sql) { throw new RuntimeException('TEST FAILURE: a database write/transaction was attempted.'); }
    public function get_row($sql, $format) {
        preg_match('/WHERE ID = (\d+)/', $sql, $match);
        return $this->rows[(int) $match[1]] ?? null;
    }
    public function get_results($sql, $format) {
        if (strpos($sql, 'test_options') !== false) {
            $options = ['home' => 'https://staging2.badcreditautoloans.net', 'page_on_front' => '21', 'show_on_front' => 'page', 'siteurl' => 'https://staging2.badcreditautoloans.net'];
            return array_map(fn($key, $value) => ['option_name' => $key, 'option_value' => $value], array_keys($options), $options);
        }
        preg_match('/post_id = (\d+)/', $sql, $match);
        return $this->meta[(int) $match[1]] ?? [];
    }
    public function get_col($sql) {
        preg_match("/post_name = '([^']+)'/", $sql, $match);
        $ids = [];
        foreach ($this->rows as $row) {
            if ($row['post_name'] === $match[1] && in_array($row['post_type'], ['page', 'attachment'], true)) { $ids[] = $row['ID']; }
        }
        return $ids;
    }
}
$checks = 0;
function check(bool $condition, string $message): void {
    global $checks;
    ++$checks;
    if (!$condition) { throw new RuntimeException($message); }
}
function rejects(callable $callback, string $reason): void {
    try { $callback(); } catch (Throwable $error) {
        check(strpos($error->getMessage(), $reason) !== false, 'Unexpected rejection: ' . $error->getMessage());
        return;
    }
    throw new RuntimeException('Expected rejection: ' . $reason);
}
$root = dirname(__DIR__);
$test_directory = DIRECTORY_SEPARATOR === '/' ? realpath(getenv('HOME')) : sys_get_temp_dir();
$temporary = $test_directory . '/bcal-read-only-tests-' . bin2hex(random_bytes(8));
mkdir($temporary, 0700);
$fixture_files = [];
$fixture = function ($name, $value) use ($temporary, &$fixture_files): string {
    $path = $temporary . '/' . $name;
    file_put_contents($path, bcal_json_encode($value));
    $fixture_files[] = $path;
    return $path;
};
try {
    $original = bcal_content_read($root . '/content/home.json');
    $staging = bcal_content_read($root . '/content/home-staging.json');
    check($staging === bcal_staging_content($original), 'Staging must match the mapped original exactly.');
    check(substr(file_get_contents($root . '/content/home-staging.json'), 0, 3) !== "\xEF\xBB\xBF", 'Staging must not have a BOM.');
    check(bcal_validate_content($staging)['blocks'] === preg_match_all('/<!--\s+wp:/', $original), 'Block counts differ from the original.');
    $bom_file = $fixture('bom.json', 'test');
    file_put_contents($bom_file, "\xEF\xBB\xBF" . file_get_contents($bom_file));
    check(bcal_json_read($bom_file) === 'test', 'Source BOM handling failed.');
    $structured = $fixture('structured.json', ['format_version' => 1, 'post_type' => 'page', 'content' => $staging]);
    check(bcal_content_read($structured) === $staging, 'Structured exports must preserve the original content.');
    rejects(fn() => bcal_content_read($fixture('wrong-type.json', ['title' => 'SEO only'])), 'Expected a Gutenberg');
    rejects(fn() => bcal_validate_content('<!-- wp:group --><!-- /wp:paragraph -->'), 'Mismatched');
    rejects(fn() => bcal_validate_content('<!-- wp:group -->'), 'Unclosed');
    rejects(fn() => bcal_validate_content('<!-- wp:paragraph {bad} --><!-- /wp:paragraph -->'), 'Syntax error');
    rejects(fn() => bcal_validate_content('<!-- wp:html -->http://localhost:8080/a<!-- /wp:html -->'), 'Localhost');
    rejects(fn() => bcal_validate_content('<!-- wp:html -->{{theme_url}}<!-- /wp:html -->'), 'placeholder');
    rejects(fn() => bcal_validate_content('<!-- wp:block {"ref":123} /-->'), 'references');
    rejects(fn() => bcal_validate_content('<!-- wp:image {"id":42} /-->'), 'references');
    rejects(fn() => bcal_content_path($root, 'content/../wp-config.php'), 'paths');
    check(bcal_validate_content('<!-- wp:separator /-->')['blocks'] === 1, 'Self-closing block rejected.');
    $manifest = bcal_manifest($root . '/content/pages-staging.json');
    $invalid = $manifest;
    $invalid['pages'][0]['meta_input'] = ['rank_math_title' => 'Do not import'];
    rejects(fn() => bcal_manifest($fixture('seo.json', $invalid)), 'metadata');
    $invalid = $manifest;
    $invalid['pages'][] = $invalid['pages'][0];
    rejects(fn() => bcal_manifest($fixture('duplicate.json', $invalid)), 'Duplicate');
    $GLOBALS['wpdb'] = $wpdb = new BCAL_Read_Only_DB();
    $row = ['ID' => '21', 'post_type' => 'page', 'post_name' => 'home', 'post_content' => '<!-- wp:paragraph --><p>Old</p><!-- /wp:paragraph -->',
        'post_title' => 'Home', 'post_status' => 'publish', 'post_parent' => '0', 'post_author' => '7', 'post_modified_gmt' => '2026-01-01 00:00:00'];
    $meta = [['meta_id' => '1', 'meta_key' => 'rank_math_title', 'meta_value' => 'SEO team title']];
    $wpdb->rows = [21 => $row];
    $wpdb->meta = [21 => $meta];
    $deployer = new BCAL_Page_Deployer($root);
    (new ReflectionProperty($deployer, 'storage'))->setValue($deployer, $temporary);
    $build = new ReflectionMethod($deployer, 'build');
    $path = $fixture('manifest.json', $manifest);
    rejects(fn() => $build->invoke($deployer, $path, 'home'), 'baseline is unset');
    $manifest['pages'][0]['baseline_sha256'] = bcal_hash([$row, $meta]);
    $path = $fixture('manifest-ready.json', $manifest);
    [$plan, $work, $state] = $build->invoke($deployer, $path, 'home');
    check($plan['pages'][0]['action'] === 'update-content', 'Existing page should update content only.');
    check($work[0]['content'] === $staging && $state['pages'] === [], 'Dry-run must not enroll state.');
    check(!file_exists($temporary . '/state.json'), 'Dry-run wrote a ledger.');
    check($wpdb->meta[21] === $meta, 'Dry-run changed SEO metadata.');
    $wpdb->rows[21]['post_title'] = 'Team edit';
    rejects(fn() => $build->invoke($deployer, $path, 'home'), 'Page changed');
    $wpdb->rows[21] = $row;
    $wpdb->meta[21][0]['meta_value'] = 'SEO edit';
    rejects(fn() => $build->invoke($deployer, $path, 'home'), 'Page changed');
    $wpdb->meta[21] = $meta;
    $wpdb->rows[21]['post_name'] = 'different';
    rejects(fn() => $build->invoke($deployer, $path, 'home'), 'verified slug');
    $wpdb->rows[21] = $row;
    $wpdb->rows[22] = array_replace($row, ['ID' => '22']);
    rejects(fn() => $build->invoke($deployer, $path, 'home'), 'verified slug');
    unset($wpdb->rows[22]);
    $wpdb->meta[21][] = ['meta_id' => '2', 'meta_key' => '_edit_lock', 'meta_value' => time() . ':9'];
    rejects(fn() => $build->invoke($deployer, $path, 'home'), 'editor lock');
    $wpdb->meta[21] = $meta;
    $new = $manifest;
    $new['pages'][0] = array_replace($new['pages'][0], ['key' => 'about', 'slug' => 'about', 'title' => 'About', 'existing_id' => null, 'baseline_sha256' => null, 'front_page' => false]);
    $new_path = $fixture('new.json', $new);
    [$new_plan] = $build->invoke($deployer, $new_path, 'about');
    check($new_plan['pages'][0]['action'] === 'create-draft' && $new_plan['pages'][0]['id'] === null, 'New page must be a draft with a staging-assigned ID.');
    $wpdb->rows[22] = array_replace($row, ['ID' => '22', 'post_name' => 'about']);
    rejects(fn() => $build->invoke($deployer, $new_path, 'about'), 'Explicit adoption');
    unset($wpdb->rows[22]);
    $ledger = ['version' => 1, 'target_url' => $manifest['target_url'], 'pages' => ['home' => ['id' => 21, 'slug' => 'home', 'sha256' => $manifest['pages'][0]['baseline_sha256']]]];
    $fixture('state.json', $ledger);
    $wpdb->rows[21]['post_content'] = 'Team content edit';
    rejects(fn() => $build->invoke($deployer, $path, 'home'), 'Page changed');
    $reviewed = $manifest;
    $reviewed['pages'][0]['baseline_sha256'] = bcal_hash([$wpdb->rows[21], $meta]);
    $reviewed_path = $fixture('reviewed.json', $reviewed);
    rejects(fn() => $build->invoke($deployer, $reviewed_path, 'home'), 'Page changed');
    $reviewed['pages'][0]['reconcile_from_sha256'] = $ledger['pages']['home']['sha256'];
    [$review_plan] = $build->invoke($deployer, $fixture('reconciled.json', $reviewed), 'home');
    check($review_plan['pages'][0]['reconciled_from_sha256'] === $ledger['pages']['home']['sha256'], 'Explicit reconciliation was not recorded.');
    $after = array_replace($row, ['post_content' => $staging, 'post_modified_gmt' => '2026-01-02 00:00:00']);
    $wpdb->rows[21] = $after;
    $after_hash = bcal_hash([$after, $meta]);
    $ledger['pages']['home']['sha256'] = $after_hash;
    $fixture('state.json', $ledger);
    $backup = ['version' => 1, 'target_url' => $manifest['target_url'], 'pages' => ['home' => ['id' => 21, 'slug' => 'home',
        'before' => $row, 'before_content_sha256' => hash('sha256', $row['post_content']), 'after_sha256' => $after_hash]]];
    $backup_path = $fixture('backup.json', $backup);
    $rollback = new ReflectionMethod($deployer, 'rollbackBuild');
    // rollbackBuild uses privatePath, which requires a POSIX HOME. Test the invariant separately
    // through snapshot fingerprints on Windows and exercise the complete builder in Linux CI.
    if (DIRECTORY_SEPARATOR === '/') {
        [$rollback_plan, $rollback_work] = $rollback->invoke($deployer, $backup_path, 'home');
        check($rollback_plan['operation'] === 'rollback' && $rollback_work[0]['content'] === $row['post_content'], 'Rollback must restore content only.');
        $wpdb->meta[21][0]['meta_value'] = 'New SEO edit';
        rejects(fn() => $rollback->invoke($deployer, $backup_path, 'home'), 'Page changed after deployment');
        $wpdb->meta[21] = $meta;
        $backup['pages']['home']['before']['post_content'] = 'Corrupt backup';
        rejects(fn() => $rollback->invoke($deployer, $fixture('corrupt-backup.json', $backup), 'home'), 'checksum mismatch');
    } else {
        $snapshot = new ReflectionMethod($deployer, 'snapshot');
        check($snapshot->invoke($deployer, 21)['sha256'] === $after_hash, 'Rollback fingerprint differs from recorded after-state.');
        $wpdb->meta[21][0]['meta_value'] = 'New SEO edit';
        check($snapshot->invoke($deployer, 21)['sha256'] !== $after_hash, 'Rollback must detect a later SEO edit.');
        $wpdb->meta[21] = $meta;
    }
    $fixture('state-pending-fixture.json', $ledger);
    rejects(fn() => $build->invoke($deployer, $path, 'home'), 'unfinished deployment journal');
    rejects(fn() => $deployer([], []), 'Only the exact staging installation');
    echo "PASS: $checks offline safety checks. No database connection or apply operation.\n";
} finally {
    foreach (array_unique($fixture_files) as $file) { if (is_file($file)) { unlink($file); } }
    rmdir($temporary);
}
