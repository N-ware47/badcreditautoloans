<?php
/** Staging-only page deployment. Loaded by WP-CLI, never by an HTTP request. */
require_once __DIR__ . '/page-content.php';

class BCAL_Page_Deployer {
    private const URL = 'https://staging2.badcreditautoloans.net';
    private const WP_ROOT = '/home/customer/www/staging2.badcreditautoloans.net/public_html';
    private string $root;
    private string $storage;

    public function __construct(string $root) {
        $this->root = $root;
    }

    private function guard(): void {
        if (PHP_SAPI !== 'cli' || !defined('WP_CLI') || WP_CLI !== true
            || rtrim((string) realpath(ABSPATH), '/') !== self::WP_ROOT
            || rtrim((string) get_option('home'), '/') !== self::URL
            || rtrim((string) get_option('siteurl'), '/') !== self::URL
            || is_multisite()) {
            throw new RuntimeException('STOP: Only the exact staging installation is allowed (path, home and siteurl).');
        }
        $private_home = getenv('HOME');
        if (!$private_home || $private_home[0] !== '/' || !realpath($private_home) || strpos($private_home, '..') !== false) {
            throw new RuntimeException('STOP: HOME must be an existing absolute private directory.');
        }
        $this->storage = rtrim(realpath($private_home), '/') . '/staging-page-backups/bcal-pages';
        $this->privatePath($this->storage . '/state.json');
    }

    private function privatePath(string $path): void {
        $private_home = rtrim((string) realpath(getenv('HOME') ?: ''), '/');
        if ($private_home === '' || strpos($path, $private_home . '/') !== 0 || strpos($path, '..') !== false
            || preg_match('~/public_html(?:/|$)~', $path) || preg_match('/[\x00-\x1f]/', $path)) {
            throw new RuntimeException('Files must be under HOME and outside every public_html directory.');
        }
        // Reject symlink traversal, including a symlink at the destination.
        $part = '';
        foreach (explode('/', $path) as $segment) {
            if ($segment === '') { continue; }
            $part .= '/' . $segment;
            if (is_link($part)) { throw new RuntimeException('Symlinks are not allowed in deployment file paths.'); }
        }
    }

    private function writePrivate(string $path, array $value, bool $replace = false): void {
        $this->privatePath($path);
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
            throw new RuntimeException('Cannot create private deployment directory.');
        }
        if ((fileperms($directory) & 0077) !== 0) {
            throw new RuntimeException('Private deployment directory must have mode 0700: ' . $directory);
        }
        if (!$replace && file_exists($path)) { throw new RuntimeException('Refusing to replace an existing file: ' . $path); }
        $temporary = $path . '.tmp-' . bin2hex(random_bytes(8));
        $handle = fopen($temporary, 'x');
        if (!$handle) { throw new RuntimeException('Cannot create private temporary file.'); }
        try {
            if (!chmod($temporary, 0600)) { throw new RuntimeException('Cannot protect deployment file.'); }
            $json = bcal_json_encode($value);
            if (fwrite($handle, $json) !== strlen($json) || !fflush($handle)
                || (function_exists('fsync') && !fsync($handle))) {
                throw new RuntimeException('Incomplete deployment file write.');
            }
        } finally { fclose($handle); }
        if (!rename($temporary, $path)) { throw new RuntimeException('Cannot publish deployment file: ' . $path); }
    }

    private function query(string $sql): void {
        global $wpdb;
        if ($wpdb->query($sql) === false) { throw new RuntimeException('Database operation failed; deployment stopped.'); }
    }

    private function row(int $id, bool $lock = false): ?array {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID = %d" . ($lock ? ' FOR UPDATE' : ''), $id), ARRAY_A);
        if ($wpdb->last_error) { throw new RuntimeException('Cannot read target page.'); }
        return $row ?: null;
    }

    private function metadata(int $id, bool $lock = false): array {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d ORDER BY meta_id" . ($lock ? ' FOR UPDATE' : ''), $id), ARRAY_A);
        if ($wpdb->last_error || !is_array($rows)) { throw new RuntimeException('Cannot read page metadata.'); }
        return $rows;
    }

    private function snapshot(int $id, bool $lock = false): array {
        $row = $this->row($id, $lock);
        if (!$row || $row['post_type'] !== 'page' || in_array($row['post_status'], ['trash', 'auto-draft'], true)) {
            throw new RuntimeException('Target is missing, trashed or is not a page: ' . $id);
        }
        $meta = $this->metadata($id, $lock);
        foreach ($meta as $entry) {
            if ($entry['meta_key'] === '_edit_lock' && (int) explode(':', $entry['meta_value'])[0] > time() - 180) {
                throw new RuntimeException('A WordPress editor lock is active for page ' . $id . '. Close the editor and wait at least three minutes.');
            }
        }
        // Whole-row + all-metadata fingerprint catches content, settings and SEO team edits.
        return ['row' => $row, 'meta' => $meta, 'sha256' => bcal_hash([$row, $meta])];
    }

    private function state(): array {
        if (glob($this->storage . '/state-pending-*.json')) {
            throw new RuntimeException('An unfinished deployment journal exists. Verify every saved after-hash before recovering the ledger; do not delete the journal blindly.');
        }
        $path = $this->storage . '/state.json';
        if (!is_file($path)) { return ['version' => 1, 'target_url' => self::URL, 'pages' => []]; }
        $state = bcal_json_read($path);
        if (($state['version'] ?? null) !== 1 || ($state['target_url'] ?? null) !== self::URL || !is_array($state['pages'] ?? null)) {
            throw new RuntimeException('Invalid server state ledger; do not reset or regenerate it automatically.');
        }
        foreach ($state['pages'] as $key => $page) {
            if (!is_int($page['id'] ?? null) || !is_string($page['slug'] ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $page['sha256'] ?? '')) {
                throw new RuntimeException('Invalid managed page state: ' . $key);
            }
        }
        return $state;
    }

    private function codeHash(): string {
        return bcal_hash(array_map('hash_file', array_fill(0, 4, 'sha256'), [
            $this->root . '/scripts/pages.php', __FILE__, __DIR__ . '/page-content.php', $this->root . '/scripts/remote-pages.sh',
        ]));
    }

    private function options(bool $lock = false): array {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN ('home','siteurl','show_on_front','page_on_front') ORDER BY option_name" . ($lock ? ' FOR UPDATE' : ''), ARRAY_A);
        if ($wpdb->last_error || count($rows) !== 4) { throw new RuntimeException('Cannot verify staging site options.'); }
        $options = array_column($rows, 'option_value', 'option_name');
        if (rtrim($options['home'], '/') !== self::URL || rtrim($options['siteurl'], '/') !== self::URL) {
            throw new RuntimeException('Database site options do not identify the staging installation.');
        }
        return $options;
    }

    private function slugIds(string $slug, bool $lock): array {
        global $wpdb;
        // Include drafts, trash and child pages. Ambiguity always stops the deployment.
        $ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('page','attachment') AND post_name = %s ORDER BY ID" . ($lock ? ' FOR UPDATE' : ''), $slug));
        if ($wpdb->last_error) { throw new RuntimeException('Cannot verify page slug uniqueness.'); }
        return array_map('intval', $ids);
    }

    private function build(string $manifest_path, ?string $selection, bool $lock = false): array {
        if (get_current_user_id() < 1 || !current_user_can('edit_pages')) {
            throw new RuntimeException('Select a staging editor with --user=<id>; no author is selected automatically.');
        }
        $manifest = bcal_manifest($manifest_path);
        $state = $this->state();
        $options = $this->options($lock);
        $plan = ['version' => 1, 'operation' => 'deploy', 'target_url' => self::URL,
            'manifest_sha256' => bcal_hash($manifest), 'code_sha256' => $this->codeHash(),
            'site' => $options, 'pages' => []];
        $work = [];
        foreach ($manifest['pages'] as $entry) {
            if ($selection !== null && $entry['key'] !== $selection) { continue; }
            $content = bcal_content_read(bcal_content_path($this->root, $entry['file']));
            $report = bcal_validate_content($content);
            foreach ($report['block_types'] as $type) {
                if (!WP_Block_Type_Registry::get_instance()->is_registered($type)) {
                    throw new RuntimeException('Block type is not registered on staging: ' . $type);
                }
            }
            $managed = $state['pages'][$entry['key']] ?? null;
            $id = $managed['id'] ?? $entry['existing_id'];
            $before = null;
            $reconciled = null;
            $slug_ids = $this->slugIds($entry['slug'], $lock);
            if ($id !== null) {
                if (!current_user_can('edit_post', $id)) { throw new RuntimeException('Selected staging user cannot edit this page.'); }
                if ($managed && (($entry['existing_id'] !== null && $entry['existing_id'] !== $id) || $managed['slug'] !== $entry['slug'])) {
                    throw new RuntimeException('Stable page identity changed: ' . $entry['key']);
                }
                $before = $this->snapshot($id, $lock);
                if ($before['row']['post_name'] !== $entry['slug'] || $slug_ids !== [$id]) {
                    throw new RuntimeException('Staging ID and unique verified slug must agree: ' . $entry['key']);
                }
                $is_front = $options['show_on_front'] === 'page' && (int) $options['page_on_front'] === $id;
                if ($is_front !== $entry['front_page']) { throw new RuntimeException('Front-page identity mismatch.'); }
                $expected = $managed['sha256'] ?? $entry['baseline_sha256'];
                if (!$expected || !hash_equals($expected, $before['sha256'])) {
                    if ($managed && ($entry['reconcile_from_sha256'] ?? null) === $managed['sha256']
                        && $entry['baseline_sha256'] === $before['sha256']) {
                        $reconciled = $managed['sha256'];
                    } else {
                        throw new RuntimeException('Page changed or adoption baseline is unset: ' . $entry['key'] . '. Inspect and review; never refresh baselines automatically.');
                    }
                }
            } elseif ($slug_ids) {
                throw new RuntimeException('A page/attachment already uses the proposed slug. Explicit adoption is required: ' . $entry['key']);
            }
            $action = !$before ? 'create-draft' : ($before['row']['post_content'] === $content ? 'unchanged' : 'update-content');
            $plan['pages'][] = ['key' => $entry['key'], 'id' => $id, 'slug' => $entry['slug'], 'action' => $action,
                'before_sha256' => $before['sha256'] ?? null, 'content_sha256' => $report['sha256'], 'bytes' => $report['bytes'],
                'blocks' => $report['blocks'], 'create_title' => $before ? null : $entry['title'],
                'preserved_title' => $before['row']['post_title'] ?? null, 'status' => $before['row']['post_status'] ?? 'draft',
                'editor_id' => get_current_user_id(), 'reconciled_from_sha256' => $reconciled];
            $work[] = ['entry' => $entry, 'before' => $before, 'content' => $content];
        }
        if (!$work) { throw new RuntimeException('No selected page exists in the manifest.'); }
        return [$plan, $work, $state];
    }

    private function rollbackBuild(string $backup_path, string $key, bool $lock = false): array {
        $this->privatePath($backup_path);
        $backup = bcal_json_read($backup_path);
        if (($backup['version'] ?? null) !== 1 || ($backup['target_url'] ?? null) !== self::URL) {
            throw new RuntimeException('Invalid staging backup.');
        }
        $state = $this->state();
        $saved = $backup['pages'][$key] ?? null;
        if (!$saved || !is_array($saved['before'] ?? null)) {
            throw new RuntimeException('No previous content for this page. New draft pages must be reviewed and trashed manually in WordPress.');
        }
        $managed = $state['pages'][$key] ?? null;
        if (!$managed || $managed['id'] !== $saved['id'] || $managed['slug'] !== $saved['slug']) {
            throw new RuntimeException('Backup and managed page identity differ.');
        }
        if (!current_user_can('edit_post', $saved['id'])) { throw new RuntimeException('Select an authorized staging editor using --user=<id>.'); }
        $before = $this->snapshot($saved['id'], $lock);
        if (!hash_equals($saved['after_sha256'], $before['sha256']) || !hash_equals($managed['sha256'], $before['sha256'])) {
            throw new RuntimeException('Page changed after deployment; rollback would overwrite another edit.');
        }
        $content = $saved['before']['post_content'];
        if (!is_string($content) || $before['row']['post_name'] !== $saved['slug']) { throw new RuntimeException('Invalid backup content/slug.'); }
        if (!hash_equals($saved['before_content_sha256'] ?? '', hash('sha256', $content))) { throw new RuntimeException('Backup content checksum mismatch.'); }
        $plan = ['version' => 1, 'operation' => 'rollback', 'target_url' => self::URL, 'backup_sha256' => hash_file('sha256', $backup_path),
            'code_sha256' => $this->codeHash(), 'site' => $this->options($lock), 'pages' => [[
                'key' => $key, 'id' => $saved['id'], 'slug' => $saved['slug'], 'action' => 'restore-content',
                'before_sha256' => $before['sha256'], 'content_sha256' => hash('sha256', $content), 'bytes' => strlen($content), 'editor_id' => get_current_user_id(),
            ]]];
        return [$plan, [['entry' => ['key' => $key, 'slug' => $saved['slug']], 'before' => $before, 'content' => $content]], $state];
    }

    private function transactionalTables(): void {
        global $wpdb;
        foreach ([$wpdb->posts, $wpdb->postmeta, $wpdb->options] as $table) {
            $engine = $wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table));
            if ($engine !== 'InnoDB') { throw new RuntimeException('Apply requires InnoDB row locks and transactions: ' . $table); }
        }
    }

    /** All decisions are rechecked with database row/range locks held through COMMIT. */
    private function apply(callable $builder, array $approved): void {
        global $wpdb;
        $this->transactionalTables();
        if (!is_dir($this->storage) && !mkdir($this->storage, 0700, true)) { throw new RuntimeException('Cannot create private state directory.'); }
        $this->privatePath($this->storage . '/deploy.lock');
        if ((fileperms($this->storage) & 0077) !== 0) { throw new RuntimeException('State directory must have mode 0700.'); }
        $file_lock = fopen($this->storage . '/deploy.lock', 'c');
        if (!$file_lock || !chmod($this->storage . '/deploy.lock', 0600) || !flock($file_lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('Another page deployment is running.');
        }
        $committed = false;
        $transaction = false;
        $changed_ids = [];
        $metadata_guard = static function ($check, $id, $key) {
            if (strpos((string) $key, 'rank_math') === 0) { throw new RuntimeException('Rank Math metadata write blocked.'); }
            return $check;
        };
        try {
            $this->query('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
            $this->query('START TRANSACTION');
            $transaction = true;
            $this->options(true); // Keep the installation identity/front-page options locked too.
            $this->guard();
            [$plan, $work, $state] = $builder(true);
            if (!hash_equals(bcal_hash($approved), bcal_hash($plan))) { throw new RuntimeException('Approved dry-run is stale. Run and review a new dry-run.'); }
            $run = gmdate('Ymd-His') . '-' . bin2hex(random_bytes(8));
            $backup_path = $this->storage . '/backups/' . $run . '.json';
            $backup = ['version' => 1, 'target_url' => self::URL, 'operation' => $plan['operation'], 'plan_sha256' => bcal_hash($plan), 'pages' => []];
            foreach ($work as $item) {
                $key = $item['entry']['key'];
                $backup['pages'][$key] = ['id' => isset($item['before']) ? (int) $item['before']['row']['ID'] : null,
                    'slug' => $item['entry']['slug'], 'before' => $item['before']['row'] ?? null,
                    'before_sha256' => $item['before']['sha256'] ?? null,
                    'before_content_sha256' => isset($item['before']) ? hash('sha256', $item['before']['row']['post_content']) : null];
            }
            // Complete, exclusive, private backup must exist BEFORE the first database write.
            $this->writePrivate($backup_path, $backup);
            foreach (['add_post_metadata', 'update_post_metadata', 'delete_post_metadata'] as $hook) { add_filter($hook, $metadata_guard, PHP_INT_MIN, 3); }
            foreach ($work as $item) {
                $entry = $item['entry'];
                $before = $item['before'];
                $id = $before ? (int) $before['row']['ID'] : null;
                if ($id) { $changed_ids[] = $id; clean_post_cache($id); }
                if (!$before) {
                    $result = wp_insert_post(wp_slash(['post_type' => 'page', 'post_status' => 'draft', 'post_parent' => 0,
                        'post_author' => get_current_user_id(), 'post_title' => $entry['title'], 'post_name' => $entry['slug'], 'post_content' => $item['content']]), true);
                } elseif ($before['row']['post_content'] !== $item['content']) {
                    $result = wp_update_post(wp_slash(['ID' => $id, 'post_content' => $item['content']]), true);
                } else { $result = $id; }
                if (is_wp_error($result) || !$result) { throw new RuntimeException('WordPress rejected the page write.'); }
                $id = (int) $result;
                $changed_ids[] = $id;
                $after = $this->snapshot($id, true);
                if ($after['row']['post_content'] !== $item['content'] || $after['row']['post_name'] !== $entry['slug']) {
                    throw new RuntimeException('WordPress changed the requested content or slug; rolling back.');
                }
                if ($before) {
                    $allowed = ['post_content', 'post_modified', 'post_modified_gmt'];
                    foreach ($before['row'] as $field => $value) {
                        if (!in_array($field, $allowed, true) && $after['row'][$field] !== $value) {
                            throw new RuntimeException('An unrelated page field changed: ' . $field);
                        }
                    }
                    // The deployment owns content only. This also catches direct SQL metadata writes by hooks.
                    if ($after['meta'] !== $before['meta']) { throw new RuntimeException('Page metadata changed; transaction aborted to preserve dashboard settings.'); }
                } elseif ($after['row']['post_status'] !== 'draft' || (int) $after['row']['post_parent'] !== 0) {
                    throw new RuntimeException('New pages must remain root-level drafts.');
                }
                if (!$before) {
                    if ($after['row']['post_title'] !== $entry['title'] || (int) $after['row']['post_author'] !== get_current_user_id()) {
                        throw new RuntimeException('WordPress changed the new page title or author.');
                    }
                    foreach ($after['meta'] as $meta) {
                        if (strpos($meta['meta_key'], 'rank_math') === 0) { throw new RuntimeException('A hook wrote Rank Math metadata on a new page; transaction aborted.'); }
                    }
                    if ($this->slugIds($entry['slug'], true) !== [$id]) { throw new RuntimeException('New page slug is ambiguous.'); }
                }
                $state['pages'][$entry['key']] = ['id' => $id, 'slug' => $entry['slug'], 'sha256' => $after['sha256']];
                $backup['pages'][$entry['key']]['id'] = $id;
                $backup['pages'][$entry['key']]['after_sha256'] = $after['sha256'];
            }
            // A later page's save hook must not alter an earlier page in the same batch.
            foreach ($backup['pages'] as $saved) {
                if (!hash_equals($saved['after_sha256'], $this->snapshot($saved['id'], true)['sha256'])) {
                    throw new RuntimeException('A selected page changed during another page save; transaction aborted.');
                }
            }
            if ($this->options(true) !== $plan['site']) { throw new RuntimeException('Site options changed during deployment.'); }
            $backup['state_after'] = $state;
            $this->writePrivate($backup_path, $backup, true);
            // Stage the ledger before COMMIT. A crash leaves a journal and fails closed on the next run.
            $pending = $this->storage . '/state-pending-' . $run . '.json';
            $this->writePrivate($pending, $state);
            $this->query('COMMIT');
            $committed = true;
            $transaction = false;
            if (!rename($pending, $this->storage . '/state.json')) {
                throw new RuntimeException('Database committed, but ledger rename failed. STOP and recover using the private backup journal: ' . $backup_path);
            }
            WP_CLI::success('Staging content committed. Metadata preserved. Backup: ' . $backup_path);
        } catch (Throwable $error) {
            if ($transaction) { $wpdb->query('ROLLBACK'); }
            throw new RuntimeException(($committed ? 'STOP: Post-commit recovery required. ' : 'STOP: No page changes committed. ') . $error->getMessage(), 0, $error);
        } finally {
            foreach (['add_post_metadata', 'update_post_metadata', 'delete_post_metadata'] as $hook) { remove_filter($hook, $metadata_guard, PHP_INT_MIN); }
            foreach ($changed_ids as $id) { clean_post_cache($id); }
            flock($file_lock, LOCK_UN);
            fclose($file_lock);
        }
    }

    public function __invoke(array $args, array $assoc): void {
        try {
            $this->guard();
            if (array_diff(array_keys($assoc), ['manifest', 'page', 'plan', 'plan-sha256', 'apply', 'backup'])) { throw new RuntimeException('Unknown command option.'); }
            $operation = $args[0] ?? 'deploy';
            if ($operation === 'inspect') {
                if (count($args) !== 2 || !ctype_digit($args[1]) || $assoc) { throw new RuntimeException('Usage: wp bc-pages inspect <staging-page-id>'); }
                $snapshot = $this->snapshot((int) $args[1]);
                $row = $snapshot['row'];
                WP_CLI::log(bcal_json_encode(['id' => (int) $row['ID'], 'slug' => $row['post_name'], 'title' => $row['post_title'],
                    'status' => $row['post_status'], 'modified_gmt' => $row['post_modified_gmt'], 'baseline_sha256' => $snapshot['sha256'],
                    'content_sha256' => hash('sha256', $row['post_content']), 'site' => $this->options()]));
                return;
            }
            if (count($args) > 1 || !in_array($operation, ['deploy', 'rollback'], true)) { throw new RuntimeException('Use inspect, deploy or rollback.'); }
            if (isset($assoc['apply']) && $assoc['apply'] !== true) { throw new RuntimeException('--apply takes no value. Omit it for dry-run.'); }
            if (!isset($assoc['apply']) && isset($assoc['plan-sha256'])) { throw new RuntimeException('--plan-sha256 is only used with --apply.'); }
            if ($operation === 'deploy' && isset($assoc['backup'])) { throw new RuntimeException('--backup is only used for rollback.'); }
            if ($operation === 'rollback' && (empty($assoc['backup']) || empty($assoc['page']) || isset($assoc['manifest']))) { throw new RuntimeException('Rollback requires --backup and --page; no --manifest.'); }
            $builder = $operation === 'deploy'
                ? fn(bool $lock) => $this->build($assoc['manifest'] ?? $this->root . '/content/pages-staging.json', $assoc['page'] ?? null, $lock)
                : fn(bool $lock) => $this->rollbackBuild($assoc['backup'], $assoc['page'], $lock);
            [$plan] = $builder(false);
            WP_CLI::log(bcal_json_encode($plan));
            WP_CLI::log('Plan SHA256: ' . bcal_hash($plan));
            if (!isset($assoc['apply'])) {
                if (isset($assoc['plan'])) { $this->writePrivate($assoc['plan'], $plan); }
                WP_CLI::success('DRY RUN passed. No database, metadata, backup or ledger writes.');
                return;
            }
            if (empty($assoc['plan']) || !preg_match('/^[a-f0-9]{64}$/D', $assoc['plan-sha256'] ?? '')) { throw new RuntimeException('Apply requires a previously reviewed --plan and its --plan-sha256.'); }
            $this->privatePath($assoc['plan']);
            $approved = bcal_json_read($assoc['plan']);
            if (!hash_equals($assoc['plan-sha256'], bcal_hash($approved)) || !hash_equals(bcal_hash($approved), bcal_hash($plan))) {
                throw new RuntimeException('Plan checksum differs or staging/source changed after dry-run.');
            }
            $this->apply($builder, $approved);
        } catch (Throwable $error) { WP_CLI::error($error->getMessage()); }
    }
}
