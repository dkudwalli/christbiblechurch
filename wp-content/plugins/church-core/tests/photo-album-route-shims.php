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

$initial_slug = 'route-shim-regression-initial';
$renamed_slug = 'route-shim-regression-renamed';
$route_root = trailingslashit(ABSPATH) . 'photo-albums';
$initial_path = $route_root . '/' . $initial_slug;
$renamed_path = $route_root . '/' . $renamed_slug;
$expected_shim = "<?php\nrequire dirname(dirname(__DIR__)) . '/index.php';\n";

foreach ([$initial_slug, $renamed_slug] as $slug) {
    $existing = get_page_by_path($slug, OBJECT, 'photo_album');

    if ($existing instanceof WP_Post) {
        wp_delete_post($existing->ID, true);
    }
}

church_test_remove_directory($initial_path);
church_test_remove_directory($renamed_path);

$post_id = wp_insert_post([
    'post_type' => 'photo_album',
    'post_status' => 'draft',
    'post_title' => 'Route Shim Regression Album',
    'post_name' => $initial_slug,
], true);

church_test_assert(! is_wp_error($post_id), 'Failed to create draft album post.');
church_test_assert(! file_exists($initial_path . '/index.php'), 'Draft albums should not create route shims.');

$publish_result = wp_update_post([
    'ID' => $post_id,
    'post_status' => 'publish',
], true);

church_test_assert(! is_wp_error($publish_result), 'Failed to publish album post.');
church_test_assert(file_exists($initial_path . '/index.php'), 'Publishing an album should create a Hostinger route shim.');
church_test_assert(file_get_contents($initial_path . '/index.php') === $expected_shim, 'Album route shim contents did not match the expected bootstrap file.');

$draft_result = wp_update_post([
    'ID' => $post_id,
    'post_status' => 'draft',
], true);

church_test_assert(! is_wp_error($draft_result), 'Failed to revert album to draft.');
church_test_assert(! file_exists($initial_path . '/index.php'), 'Drafting an album should remove its route shim.');

$republish_result = wp_update_post([
    'ID' => $post_id,
    'post_status' => 'publish',
], true);

church_test_assert(! is_wp_error($republish_result), 'Failed to republish album.');
church_test_assert(file_exists($initial_path . '/index.php'), 'Republishing an album should recreate the route shim.');

$rename_result = wp_update_post([
    'ID' => $post_id,
    'post_name' => $renamed_slug,
], true);

church_test_assert(! is_wp_error($rename_result), 'Failed to rename album slug.');
church_test_assert(! file_exists($initial_path . '/index.php'), 'Renaming an album should remove the stale shim for the previous slug.');
church_test_assert(file_exists($renamed_path . '/index.php'), 'Renaming an album should create a shim for the new slug.');

$trash_result = wp_trash_post($post_id);
church_test_assert($trash_result instanceof WP_Post, 'Failed to trash album.');
church_test_assert(! file_exists($renamed_path . '/index.php'), 'Trashing an album should remove its route shim.');

wp_delete_post($post_id, true);
church_test_remove_directory($initial_path);
church_test_remove_directory($renamed_path);

fwrite(STDOUT, "Photo album route shim checks passed.\n");
