<?php
if (! defined('ABSPATH')) {
    exit;
}

function church_theme_get_page_section_layout(WP_Post $section): string
{
    if (! class_exists('Church_Core_Page_Sections')) {
        return 'default';
    }

    return Church_Core_Page_Sections::get_layout($section->ID);
}

function church_theme_get_page_section_profiles(WP_Post $section): array
{
    if (! class_exists('Church_Core_Page_Sections')) {
        return [];
    }

    return Church_Core_Page_Sections::get_profiles($section->ID);
}

function church_theme_get_sermon_date(int $post_id): string
{
    $value = (string) get_post_meta($post_id, 'sermon_date', true);

    if ($value === '') {
        return get_the_date('', $post_id);
    }

    $timestamp = strtotime($value);

    return $timestamp ? wp_date(get_option('date_format'), $timestamp) : $value;
}

function church_theme_get_event_datetime_object(int $post_id): ?DateTimeImmutable
{
    $value = (string) get_post_meta($post_id, 'event_start', true);

    return $value === '' || ! class_exists('Church_Core_Events') ? null : Church_Core_Events::parse_datetime($value);
}

function church_theme_get_event_datetime(int $post_id): string
{
    $date = church_theme_get_event_datetime_object($post_id);

    if (! $date instanceof DateTimeImmutable) {
        return __('Date to be announced', 'church-theme');
    }

    return wp_date(get_option('date_format') . ' \a\t ' . get_option('time_format'), $date->getTimestamp(), wp_timezone());
}

function church_theme_get_event_location(int $post_id): string
{
    return (string) get_post_meta($post_id, 'event_location', true);
}


/**
 * Plain-text card/meta preview for any post: hand-written excerpt when there is
 * one, otherwise the stripped post content. Pass $use_excerpt = false for post
 * types (events) whose excerpt is not authored.
 */
function church_theme_get_post_preview(int $post_id, int $word_limit = 26, bool $use_excerpt = true): string
{
    $content = $use_excerpt ? (string) get_post_field('post_excerpt', $post_id) : '';

    if ($content === '') {
        $content = wp_strip_all_tags(strip_shortcodes((string) get_post_field('post_content', $post_id)));
    }

    $content = trim(preg_replace('/\s+/', ' ', $content) ?: '');

    return $content === '' ? '' : wp_trim_words($content, $word_limit);
}

/**
 * Order by a date meta key while KEEPING posts that have none. A bare meta_key
 * INNER-JOINs postmeta and drops them entirely; the OR meta_query LEFT-JOINs so
 * undated posts sort last instead.
 */
function church_theme_date_meta_sort_args(string $meta_key): array
{
    return [
        'meta_query' => [
            'relation' => 'OR',
            'date_present' => ['key' => $meta_key, 'compare' => 'EXISTS', 'type' => 'DATE'],
            'date_missing' => ['key' => $meta_key, 'compare' => 'NOT EXISTS'],
        ],
        'orderby' => ['date_present' => 'DESC', 'date' => 'DESC'],
    ];
}

function church_theme_get_photo_album_date(int $post_id): string
{
    $value = class_exists('Church_Core_Photo_Albums')
        ? Church_Core_Photo_Albums::get_album_date($post_id)
        : (string) get_post_meta($post_id, 'album_date', true);

    if ($value === '') {
        return get_the_date('', $post_id);
    }

    $timestamp = strtotime($value);

    return $timestamp ? wp_date(get_option('date_format'), $timestamp) : $value;
}



function church_theme_get_photo_album_query(int $posts_per_page = -1): WP_Query
{
    $meta_key = class_exists('Church_Core_Photo_Albums')
        ? Church_Core_Photo_Albums::DATE_META_KEY
        : 'album_date';

    return new WP_Query(array_merge([
        'post_type' => 'photo_album',
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    ], church_theme_date_meta_sort_args($meta_key)));
}

function church_theme_get_event_query(bool $upcoming, int $posts_per_page = -1): WP_Query
{
    $paginate = $posts_per_page !== -1;
    // Use a custom query parameter so path-based WordPress paging (/page/2/) is not triggered,
    // which avoids 404s when the main archive query doesn't know about this limit.
    $current_page = $paginate ? max(1, (int) ($_GET['past_page'] ?? 1)) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    return new WP_Query([
        'post_type' => 'event',
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'paged' => $current_page,
        'meta_key' => 'event_start',
        'orderby' => 'meta_value',
        'meta_type' => 'DATETIME',
        'order' => $upcoming ? 'ASC' : 'DESC',
        'no_found_rows' => ! $paginate,
        'meta_query' => [[
            'key' => 'event_start',
            'value' => current_time('mysql'),
            'compare' => $upcoming ? '>=' : '<',
            'type' => 'DATETIME',
        ]],
    ]);
}

function church_theme_get_latest_sermon_query(int $limit = 1, int $offset = 0): WP_Query
{
    return new WP_Query(array_merge([
        'post_type' => 'sermon',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'no_found_rows' => true,
    ], church_theme_date_meta_sort_args('sermon_date')));
}

function church_theme_get_related_sermon_query(int $post_id, int $limit = 3): array
{
    $series_term = church_theme_get_sermon_primary_term($post_id, 'series');
    $base_args = array_merge([
        'post_type' => 'sermon',
        'posts_per_page' => $limit,
        'post__not_in' => [$post_id],
        'no_found_rows' => true,
    ], church_theme_date_meta_sort_args('sermon_date'));

    if ($series_term) {
        $series_args = $base_args;
        $series_args['tax_query'] = [[
            'taxonomy' => 'series',
            'field' => 'term_id',
            'terms' => [$series_term->term_id],
        ]];
        $series_query = new WP_Query($series_args);

        if ($series_query->have_posts()) {
            return [
                'query' => $series_query,
                'title' => sprintf(__('More in %s', 'church-theme'), $series_term->name),
            ];
        }
    }

    return [
        'query' => new WP_Query($base_args),
        'title' => __('Recent Sermons', 'church-theme'),
    ];
}

function church_theme_get_sermon_audio_url(int $post_id): string
{
    return (string) get_post_meta($post_id, 'audio_url', true);
}

function church_theme_get_photo_album_photo_ids(int $post_id): array
{
    if (! class_exists('Church_Core_Photo_Albums')) {
        return [];
    }

    return Church_Core_Photo_Albums::get_photo_ids($post_id);
}

function church_theme_get_photo_album_photo_assets(int $post_id): array
{
    $assets = [];

    foreach (church_theme_get_photo_album_photo_ids($post_id) as $attachment_id) {
        $asset = church_theme_get_attachment_image_asset((int) $attachment_id);

        if (! is_array($asset) || empty($asset['src'])) {
            continue;
        }

        $asset['id'] = (int) $attachment_id;
        $asset['title'] = get_the_title($attachment_id);
        $assets[] = $asset;
    }

    return $assets;
}

function church_theme_get_photo_album_cover_asset(int $post_id): ?array
{
    if (has_post_thumbnail($post_id)) {
        $asset = church_theme_get_attachment_image_asset((int) get_post_thumbnail_id($post_id));

        if (is_array($asset) && ! empty($asset['src'])) {
            return $asset;
        }
    }

    // Resolve one attachment at a time and stop at the first usable one. Building
    // the whole album's assets just to take [0] cost ~2 uncached queries plus a
    // srcset build per photo, on an unpaginated gallery. The skip below matters:
    // an album whose first photo was deleted must still fall through to the next.
    foreach (church_theme_get_photo_album_photo_ids($post_id) as $attachment_id) {
        $asset = church_theme_get_attachment_image_asset((int) $attachment_id);

        if (! is_array($asset) || empty($asset['src'])) {
            continue;
        }

        $asset['id'] = (int) $attachment_id;
        $asset['title'] = get_the_title($attachment_id);

        return $asset;
    }

    return null;
}

function church_theme_get_sermon_primary_term(int $post_id, string $taxonomy): ?WP_Term
{
    $terms = get_the_terms($post_id, $taxonomy);

    if (! is_array($terms) || $terms === [] || is_wp_error($terms)) {
        return null;
    }

    return $terms[0] instanceof WP_Term ? $terms[0] : null;
}

function church_theme_get_sermon_term_url(?WP_Term $term): string
{
    if (! $term instanceof WP_Term) {
        return church_theme_get_sermon_archive_url();
    }

    $link = get_term_link($term);

    return is_string($link) ? $link : church_theme_get_sermon_archive_url();
}

function church_theme_get_sermon_active_term_slug(string $taxonomy): string
{
    if (is_tax($taxonomy)) {
        $term = get_queried_object();

        if ($term instanceof WP_Term && $term->taxonomy === $taxonomy) {
            return $term->slug;
        }
    }

    return isset($_GET[$taxonomy]) ? sanitize_title(wp_unslash((string) $_GET[$taxonomy])) : '';
}

function church_theme_get_sermon_archive_context(): array
{
    if (is_tax(['speaker', 'series'])) {
        $term = get_queried_object();

        if ($term instanceof WP_Term) {
            $summary = trim(wp_strip_all_tags((string) $term->description));

            if ($summary === '') {
                if ($term->taxonomy === 'speaker') {
                    $summary = sprintf(__('Messages preached by %s.', 'church-theme'), $term->name);
                }

                if ($term->taxonomy === 'series') {
                    $summary = sprintf(__('Messages from the %s series.', 'church-theme'), $term->name);
                }
            }

            return [
                'title' => $term->name,
                'summary' => $summary,
            ];
        }
    }

    $title = post_type_archive_title('', false);

    return [
        'title' => $title !== '' ? $title : __('Sermons', 'church-theme'),
        'summary' => __('Browse recent teaching and upcoming archive imports from Crossroad South Church.', 'church-theme'),
    ];
}
