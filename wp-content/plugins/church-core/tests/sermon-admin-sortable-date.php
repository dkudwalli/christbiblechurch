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

$created_post_ids = [];

register_shutdown_function(static function () use (&$created_post_ids): void {
    foreach ($created_post_ids as $post_id) {
        wp_delete_post($post_id, true);
    }
});

church_test_assert(
    has_filter('manage_edit-sermon_sortable_columns', [Church_Core_Sermons::class, 'sermon_sortable_columns']) !== false,
    'Sermon Date sortable-column hook is not registered.'
);

$sortable_columns = apply_filters('manage_edit-sermon_sortable_columns', []);
$sermon_date_sortable = $sortable_columns['sermon_date'] ?? null;

church_test_assert($sermon_date_sortable !== null, 'Sermon Date column is not registered as sortable.');
church_test_assert(is_callable([Church_Core_Sermons::class, 'tune_admin_list_query']), 'Admin sermon list query helper is not available.');

$older_sermon_id = wp_insert_post([
    'post_type' => 'sermon',
    'post_status' => 'publish',
    'post_title' => 'Sortable Sermon Date Older',
], true);

church_test_assert(! is_wp_error($older_sermon_id), 'Failed to create the older sermon.');
$created_post_ids[] = $older_sermon_id;
update_post_meta($older_sermon_id, 'sermon_date', '2024-01-07');

$recent_lower_id = wp_insert_post([
    'post_type' => 'sermon',
    'post_status' => 'publish',
    'post_title' => 'Sortable Sermon Date Recent Lower ID',
], true);

church_test_assert(! is_wp_error($recent_lower_id), 'Failed to create the first recent sermon.');
$created_post_ids[] = $recent_lower_id;
update_post_meta($recent_lower_id, 'sermon_date', '2025-05-04');

$recent_higher_id = wp_insert_post([
    'post_type' => 'sermon',
    'post_status' => 'publish',
    'post_title' => 'Sortable Sermon Date Recent Higher ID',
], true);

church_test_assert(! is_wp_error($recent_higher_id), 'Failed to create the second recent sermon.');
$created_post_ids[] = $recent_higher_id;
update_post_meta($recent_higher_id, 'sermon_date', '2025-05-04');

$missing_date_id = wp_insert_post([
    'post_type' => 'sermon',
    'post_status' => 'publish',
    'post_title' => 'Sortable Sermon Date Missing',
], true);

church_test_assert(! is_wp_error($missing_date_id), 'Failed to create the sermon without a date.');
$created_post_ids[] = $missing_date_id;

$sermon_ids = [$older_sermon_id, $recent_lower_id, $recent_higher_id, $missing_date_id];

$run_sorted_query = static function (string $order) use ($sermon_ids): array {
    $query = new WP_Query();
    $query->init();
    $query->set('post_type', 'sermon');
    $query->set('post_status', 'publish');
    $query->set('post__in', $sermon_ids);
    $query->set('orderby', 'sermon_date');
    $query->set('order', $order);
    $query->set('posts_per_page', -1);
    $query->set('fields', 'ids');

    Church_Core_Sermons::tune_admin_list_query($query);

    return $query->get_posts();
};

$desc_results = $run_sorted_query('DESC');
$asc_results = $run_sorted_query('ASC');

$desc_expected = [$recent_higher_id, $recent_lower_id, $older_sermon_id, $missing_date_id];
$asc_expected = [$older_sermon_id, $recent_lower_id, $recent_higher_id, $missing_date_id];

church_test_assert(
    array_slice($desc_results, 0, 4) === $desc_expected,
    'Descending sermon date sort did not keep missing dates last with deterministic tie ordering.'
);

church_test_assert(
    array_slice($asc_results, 0, 4) === $asc_expected,
    'Ascending sermon date sort did not keep missing dates last with deterministic tie ordering.'
);

fwrite(STDOUT, "Sermon admin sortable date checks passed.\n");
