<?php
/**
 * Verifies the ADDITIVE sermon route-shim generator (Church_Core_Sermon_Route_Shims):
 * publishing a sermon creates sermons/<slug>/index.php with the committed body,
 * and — unlike Photo_Albums — drafting/trashing NEVER removes it (create-only, so
 * runtime can't fight the committed baseline on redeploy).
 *
 * Runs via WP-CLI `wp eval-file`. Needs a writable ABSPATH (run the wp-cli
 * container as root locally/CI; on prod the web server already owns the root).
 */

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

$slug = 'sermon-route-shim-regression';
$route_root = trailingslashit(ABSPATH) . 'sermons';
$shim_dir = $route_root . '/' . $slug;
$shim_file = $shim_dir . '/index.php';
$expected_shim = "<?php\n\nrequire dirname(dirname(__DIR__)) . '/index.php';\n";

$existing = get_page_by_path($slug, OBJECT, 'sermon');

if ($existing instanceof WP_Post) {
    wp_delete_post($existing->ID, true);
}

church_test_remove_directory($shim_dir);

// Draft sermon: no shim yet (generator only fires on publish).
$post_id = wp_insert_post([
    'post_type' => 'sermon',
    'post_status' => 'draft',
    'post_title' => 'Sermon Route Shim Regression',
    'post_name' => $slug,
], true);

church_test_assert(! is_wp_error($post_id), 'Failed to create draft sermon post.');
church_test_assert(! file_exists($shim_file), 'Draft sermons should not create route shims.');

// Publish: shim is created with the exact committed body.
$publish_result = wp_update_post(['ID' => $post_id, 'post_status' => 'publish'], true);
church_test_assert(! is_wp_error($publish_result), 'Failed to publish sermon post.');
church_test_assert(file_exists($shim_file), 'Publishing a sermon should create a Hostinger route shim.');
church_test_assert(
    file_get_contents($shim_file) === $expected_shim,
    'Sermon route shim contents did not match the committed sermon shim format.'
);

// ADDITIVE CONTRACT: drafting must NOT remove the shim (create-only — no runtime
// removal, so a redeploy of the committed baseline never conflicts).
$draft_result = wp_update_post(['ID' => $post_id, 'post_status' => 'draft'], true);
church_test_assert(! is_wp_error($draft_result), 'Failed to revert sermon to draft.');
church_test_assert(file_exists($shim_file), 'Drafting a sermon must NOT remove its shim (additive, create-only).');

// ADDITIVE CONTRACT: trashing must NOT remove the shim either.
$trash_result = wp_trash_post($post_id);
church_test_assert($trash_result instanceof WP_Post, 'Failed to trash sermon.');
church_test_assert(file_exists($shim_file), 'Trashing a sermon must NOT remove its shim (additive, create-only).');

// Cleanup (this test created the shim, so it removes it; runtime never would).
wp_delete_post($post_id, true);
church_test_remove_directory($shim_dir);

fwrite(STDOUT, "Sermon route shim checks passed.\n");
