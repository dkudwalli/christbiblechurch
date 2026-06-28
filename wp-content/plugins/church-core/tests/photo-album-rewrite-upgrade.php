<?php
if (! defined('ABSPATH')) {
    exit(1);
}

function church_test_fail(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function church_test_assert(bool $condition, string $message): void
{
    if (! $condition) {
        church_test_fail($message);
    }
}

function church_test_remove_directory(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $items = scandir($path);

    if (! is_array($items)) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $item_path = $path . DIRECTORY_SEPARATOR . $item;

        if (is_dir($item_path)) {
            church_test_remove_directory($item_path);
            continue;
        }

        @unlink($item_path);
    }

    @rmdir($path);
}

function church_test_photo_album_rewrite_rules(array $rules): array
{
    $photo_album_rules = [];

    foreach ($rules as $rule => $query) {
        if (str_contains((string) $rule, 'photo-albums/') || str_contains((string) $query, 'photo_album=')) {
            $photo_album_rules[(string) $rule] = (string) $query;
        }
    }

    return $photo_album_rules;
}

$rewrite_version_option = 'church_core_photo_album_rewrite_version';
$test_slug = 'rewrite-upgrade-regression';
$route_root = trailingslashit(ABSPATH) . 'photo-albums';
$route_path = $route_root . '/' . $test_slug;
$version_marker_missing = '__missing__';

$existing = get_page_by_path($test_slug, OBJECT, 'photo_album');

if ($existing instanceof WP_Post) {
    wp_delete_post($existing->ID, true);
}

Church_Core_Photo_Albums::register_content();
flush_rewrite_rules(false);

$original_rules = get_option('rewrite_rules', []);
$original_rewrite_version = get_option($rewrite_version_option, $version_marker_missing);
$created_post_id = 0;

$created_post_id = wp_insert_post([
    'post_type' => 'photo_album',
    'post_status' => 'publish',
    'post_title' => 'Rewrite Upgrade Regression Album',
    'post_name' => $test_slug,
], true);

church_test_assert(! is_wp_error($created_post_id), 'Failed to create the rewrite upgrade regression album.');

register_shutdown_function(static function () use ($created_post_id, $original_rewrite_version, $original_rules, $rewrite_version_option, $route_path, $version_marker_missing): void {
    if ($created_post_id > 0) {
        wp_delete_post($created_post_id, true);
    }

    if ($original_rewrite_version === $version_marker_missing) {
        delete_option($rewrite_version_option);
    } else {
        update_option($rewrite_version_option, $original_rewrite_version, false);
    }

    update_option('rewrite_rules', $original_rules, false);
    church_test_remove_directory($route_path);
});

// The connection target and Host header are configurable so this test can run
// from a separate container (e.g. wp-cli) where the site is reachable by service
// name rather than 127.0.0.1. Defaults preserve the original local behavior.
$test_base_url = rtrim((string) (getenv('CHURCH_TEST_BASE_URL') ?: 'http://127.0.0.1'), '/');
$test_host_header = (string) (getenv('CHURCH_TEST_HOST') ?: 'localhost:8080');

$query_var_response = wp_remote_get(
    $test_base_url . '/?photo_album=' . rawurlencode($test_slug),
    [
        'headers' => [
            'Host' => $test_host_header,
        ],
        'redirection' => 0,
    ]
);

church_test_assert(! is_wp_error($query_var_response), 'Failed to request the photo_album query-var URL.');

$query_var_body = wp_remote_retrieve_body($query_var_response);

church_test_assert(
    wp_remote_retrieve_response_code($query_var_response) === 200
    && str_contains($query_var_body, 'Rewrite Upgrade Regression Album')
    && str_contains($query_var_body, 'album-detail'),
    'Published photo albums should resolve through the photo_album query var.'
);

$baseline_rules = church_test_photo_album_rewrite_rules((array) $original_rules);
church_test_assert($baseline_rules !== [], 'Baseline photo album rewrite rules were not present.');

$rules_without_photo_albums = [];

foreach ((array) $original_rules as $rule => $query) {
    if (str_contains((string) $rule, 'photo-albums/') || str_contains((string) $query, 'photo_album=')) {
        continue;
    }

    $rules_without_photo_albums[(string) $rule] = (string) $query;
}

update_option('rewrite_rules', $rules_without_photo_albums, false);
delete_option($rewrite_version_option);

church_test_assert(
    church_test_photo_album_rewrite_rules((array) get_option('rewrite_rules', [])) === [],
    'Failed to clear photo album rewrite rules from the stored rewrite_rules option.'
);

church_test_assert(
    is_callable(['Church_Core_Photo_Albums', 'maybe_run_rewrite_upgrade']),
    'Photo album rewrite upgrade hook is not available.'
);

Church_Core_Photo_Albums::maybe_run_rewrite_upgrade();

$restored_rules = church_test_photo_album_rewrite_rules((array) get_option('rewrite_rules', []));

church_test_assert($restored_rules !== [], 'Rewrite upgrade should restore photo album permalink rules.');
church_test_assert(get_option($rewrite_version_option, '') === '1', 'Rewrite upgrade should persist the current version marker.');

fwrite(STDOUT, "Photo album rewrite upgrade checks passed.\n");
