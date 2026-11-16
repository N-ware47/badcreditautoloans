<?php
/** Pure file/content helpers. Never loads WordPress or connects to a database. */

function bcal_json_read(string $path) {
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException('Cannot read JSON file: ' . $path);
    }
    // Windows PowerShell exports commonly include this UTF-8 BOM.
    if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
        $raw = substr($raw, 3);
    }
    return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
}

function bcal_json_encode($value): string {
    return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
}

function bcal_hash($value): string {
    return hash('sha256', bcal_json_encode($value));
}

function bcal_content_read(string $path): string {
    $value = bcal_json_read($path);
    if (is_string($value)) {
        return $value;
    }
    if (is_array($value) && ($value['format_version'] ?? null) === 1 && ($value['post_type'] ?? null) === 'page'
        && isset($value['content']) && is_string($value['content'])) {
        return $value['content'];
    }
    throw new RuntimeException('Expected a Gutenberg JSON string or a version 1 page export: ' . $path);
}

function bcal_staging_content(string $content): string {
    return str_replace(
        ['https://localhost/badcreditautoloans/wordpress', 'http://localhost/badcreditautoloans/wordpress', '{{theme_url}}'],
        ['https://staging2.badcreditautoloans.net', 'https://staging2.badcreditautoloans.net', 'https://staging2.badcreditautoloans.net/wp-content/themes/badcreditautoloans-child'],
        $content
    );
}

/** Validate delimiter nesting and JSON attributes; WP's parser is intentionally forgiving. */
function bcal_validate_content(string $content): array {
    if (trim($content) === '' || preg_match('//u', $content) !== 1) {
        throw new RuntimeException('Content must be nonempty UTF-8.');
    }
    if (preg_match('~(?:https?:)?//(?:localhost|127\.0\.0\.1|\[::1\])(?=[:/"\s]|$)~i', str_replace('\\/', '/', $content))
        || preg_match('/\{\{[^}]+\}\}/', $content)) {
        throw new RuntimeException('Localhost URL or unresolved placeholder detected.');
    }
    preg_match_all('/<!--\s*(\/?)wp:([a-z0-9_-]+(?:\/[a-z0-9_-]+)?)(.*?)-->/s', $content, $matches, PREG_SET_ORDER);
    if (!$matches || count($matches) !== preg_match_all('/<!--\s*\/?wp:/', $content)) {
        throw new RuntimeException('Missing or malformed Gutenberg delimiters.');
    }
    $stack = [];
    $names = [];
    $count = 0;
    foreach ($matches as $marker) {
        $name = $marker[2];
        $tail = trim($marker[3]);
        if ($marker[1] === '/') {
            if ($tail !== '' || array_pop($stack) !== $name) {
                throw new RuntimeException('Mismatched Gutenberg closing delimiter: ' . $name);
            }
            continue;
        }
        $self_closing = substr($tail, -1) === '/';
        if ($self_closing) {
            $tail = trim(substr($tail, 0, -1));
        }
        $attrs = $tail === '' ? [] : json_decode($tail, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($attrs) || ($tail !== '' && $tail[0] !== '{')) {
            throw new RuntimeException('Block attributes must be a JSON object.');
        }
        $canonical = strpos($name, '/') === false ? 'core/' . $name : $name;
        if (isset($attrs['ref']) || (in_array($canonical, ['core/image', 'core/cover', 'core/media-text', 'core/video', 'core/audio', 'core/file', 'core/gallery'], true)
            && (isset($attrs['id']) || isset($attrs['ids']) || isset($attrs['mediaId'])))) {
            throw new RuntimeException('Local database/media references need a verified staging mapping: ' . $canonical);
        }
        $names[$canonical] = true;
        ++$count;
        if (!$self_closing) {
            $stack[] = $name;
        }
    }
    if ($stack) {
        throw new RuntimeException('Unclosed Gutenberg delimiter: ' . end($stack));
    }
    // Used by local verification and the staging importer when the parser is available.
    if (class_exists('WP_Block_Parser')) {
        $blocks = (new WP_Block_Parser())->parse($content);
        $parsed_count = 0;
        $walk = function (array $items) use (&$walk, &$parsed_count): void {
            foreach ($items as $item) {
                if ($item['blockName'] !== null) {
                    ++$parsed_count;
                } elseif (trim($item['innerHTML']) !== '') {
                    throw new RuntimeException('Unexpected content outside Gutenberg blocks.');
                }
                $walk($item['innerBlocks']);
            }
        };
        $walk($blocks);
        if ($parsed_count !== $count) {
            throw new RuntimeException('WordPress parser and delimiter counts differ.');
        }
    }
    return ['bytes' => strlen($content), 'sha256' => hash('sha256', $content), 'blocks' => $count, 'block_types' => array_keys($names)];
}

function bcal_content_path(string $root, string $relative): string {
    if (!preg_match('~^content/[a-zA-Z0-9_./-]+\.json$~D', $relative) || strpos($relative, '..') !== false) {
        throw new RuntimeException('Content paths must stay under content/ and end in .json.');
    }
    $path = realpath($root . '/' . $relative);
    $content_root = realpath($root . '/content');
    if (!$path || !$content_root || strpos(str_replace('\\', '/', $path), str_replace('\\', '/', $content_root) . '/') !== 0) {
        throw new RuntimeException('Missing content file or path outside content/.');
    }
    return $path;
}

function bcal_manifest(string $path): array {
    $manifest = bcal_json_read($path);
    if (!is_array($manifest) || array_diff(array_keys($manifest), ['version', 'target_url', 'pages'])
        || ($manifest['version'] ?? null) !== 1 || ($manifest['target_url'] ?? null) !== 'https://staging2.badcreditautoloans.net'
        || empty($manifest['pages']) || !is_array($manifest['pages']) || !array_is_list($manifest['pages'])) {
        throw new RuntimeException('Invalid staging page manifest.');
    }
    $keys = $slugs = $ids = [];
    foreach ($manifest['pages'] as $page) {
        if (!is_array($page) || array_diff(array_keys($page), ['key', 'slug', 'file', 'title', 'existing_id', 'baseline_sha256', 'front_page', 'reconcile_from_sha256'])
            || !preg_match('/^[a-z][a-z0-9-]{0,79}$/D', $page['key'] ?? '')
            || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $page['slug'] ?? '')
            || !is_string($page['file'] ?? null) || !is_string($page['title'] ?? null) || trim($page['title']) === ''
            || !array_key_exists('existing_id', $page) || !array_key_exists('baseline_sha256', $page)
            || !is_bool($page['front_page'] ?? null)) {
            throw new RuntimeException('Invalid page entry; SEO and other metadata fields are forbidden.');
        }
        if ($page['existing_id'] !== null && (!is_int($page['existing_id']) || $page['existing_id'] < 1)) {
            throw new RuntimeException('existing_id must be a positive staging ID or null.');
        }
        if ($page['baseline_sha256'] !== null && !preg_match('/^[a-f0-9]{64}$/D', $page['baseline_sha256'])) {
            throw new RuntimeException('Invalid adoption baseline hash.');
        }
        if (isset($page['reconcile_from_sha256']) && !preg_match('/^[a-f0-9]{64}$/D', $page['reconcile_from_sha256'])) {
            throw new RuntimeException('Invalid reconciliation source hash.');
        }
        if ($page['existing_id'] === null && ($page['baseline_sha256'] !== null || $page['front_page'])) {
            throw new RuntimeException('New pages cannot have adoption baselines or become the front page.');
        }
        if (isset($keys[$page['key']]) || isset($slugs[$page['slug']]) || ($page['existing_id'] !== null && isset($ids[$page['existing_id']]))) {
            throw new RuntimeException('Duplicate page key, slug or staging ID.');
        }
        $keys[$page['key']] = $slugs[$page['slug']] = true;
        if ($page['existing_id'] !== null) {
            $ids[$page['existing_id']] = true;
        }
    }
    return $manifest;
}
