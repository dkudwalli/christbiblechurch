<?php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Build a plain-text meta/OG description for the current request.
 *
 * Runs at wp_head (before the loop), so it derives everything from the queried
 * object rather than loop globals. Falls back to the site tagline, then the
 * mission statement, and is trimmed to ~160 characters.
 */
function church_theme_seo_description(int $post_id): string
{
    $text = '';

    if (is_front_page()) {
        $text = church_theme_get_mod('welcome_summary');
    } elseif (is_singular('sermon') && $post_id > 0) {
        $text = church_theme_get_post_preview($post_id, 32);
    } elseif (is_singular('event') && $post_id > 0) {
        $text = church_theme_get_post_preview($post_id, 32, false);
    } elseif (is_singular() && $post_id > 0) {
        $text = (string) get_the_excerpt($post_id);
    } elseif (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        $text = $term instanceof WP_Term ? (string) $term->description : '';
    }

    $text = trim(wp_strip_all_tags($text));

    if ($text === '') {
        $text = trim(wp_strip_all_tags((string) get_bloginfo('description')));
    }
    if ($text === '') {
        $text = trim(wp_strip_all_tags(church_theme_get_mod('mission_statement')));
    }

    return trim(mb_strimwidth($text, 0, 160, '…'));
}

/**
 * Resolve an absolute Open Graph image (url + dimensions) for the current request.
 *
 * Priority: sermon YouTube thumbnail -> featured image -> Customizer override ->
 * the committed 1200x630 JPG default. JPG/PNG are used deliberately because some
 * link-preview scrapers (e.g. WhatsApp) do not render WebP og:images.
 */
function church_theme_seo_image(int $post_id): array
{
    if (is_singular('sermon') && $post_id > 0) {
        $video_id = (string) get_post_meta($post_id, 'youtube_video_id', true);
        if ($video_id !== '') {
            return [
                'url' => 'https://i.ytimg.com/vi/' . rawurlencode($video_id) . '/hqdefault.jpg',
                'width' => 480,
                'height' => 360,
            ];
        }
    }

    if ($post_id > 0 && has_post_thumbnail($post_id)) {
        $asset = church_theme_get_attachment_image_asset((int) get_post_thumbnail_id($post_id));
        if (is_array($asset) && ! empty($asset['src'])) {
            return [
                'url' => (string) $asset['src'],
                'width' => (int) ($asset['width'] ?? 0),
                'height' => (int) ($asset['height'] ?? 0),
            ];
        }
    }

    $custom = trim(church_theme_get_mod('default_og_image'));
    if ($custom !== '') {
        return ['url' => $custom, 'width' => 0, 'height' => 0];
    }

    return [
        'url' => get_template_directory_uri() . '/assets/images/crossroads/og-default.jpg',
        'width' => 1200,
        'height' => 630,
    ];
}

/**
 * Canonical-style URL for og:url, consistent with WP core's rel=canonical.
 */
function church_theme_seo_url(int $post_id): string
{
    if (is_front_page()) {
        return home_url('/');
    }

    if (is_singular() && $post_id > 0) {
        $canonical = wp_get_canonical_url($post_id);
        if (is_string($canonical) && $canonical !== '') {
            return $canonical;
        }

        $permalink = get_permalink($post_id);
        if (is_string($permalink) && $permalink !== '') {
            return $permalink;
        }
    }

    if (is_post_type_archive()) {
        $post_type = (string) get_query_var('post_type');
        $link = $post_type !== '' ? get_post_type_archive_link($post_type) : false;
        if (is_string($link) && $link !== '') {
            return $link;
        }
    }

    if (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $link = get_term_link($term);
            if (is_string($link)) {
                return $link;
            }
        }
    }

    return home_url('/');
}

/**
 * Emit meta description + Open Graph + Twitter card tags.
 *
 * WP core already owns <title> (title-tag support) and rel=canonical, so those
 * are intentionally not duplicated here.
 */
function church_theme_seo_meta(): void
{
    $post_id = (int) get_queried_object_id();
    $title = wp_get_document_title();
    $description = church_theme_seo_description($post_id);
    $image = church_theme_seo_image($post_id);
    $url = church_theme_seo_url($post_id);
    $type = is_singular(['sermon', 'event', 'post']) ? 'article' : 'website';

    $tags = [];

    if ($description !== '') {
        $tags[] = sprintf('<meta name="description" content="%s">', esc_attr($description));
    }

    $tags[] = sprintf('<meta property="og:type" content="%s">', esc_attr($type));
    $tags[] = sprintf('<meta property="og:site_name" content="%s">', esc_attr((string) get_bloginfo('name')));
    $tags[] = sprintf('<meta property="og:locale" content="%s">', esc_attr(get_locale()));
    $tags[] = sprintf('<meta property="og:title" content="%s">', esc_attr($title));

    if ($description !== '') {
        $tags[] = sprintf('<meta property="og:description" content="%s">', esc_attr($description));
    }

    $tags[] = sprintf('<meta property="og:url" content="%s">', esc_url($url));

    if (! empty($image['url'])) {
        $tags[] = sprintf('<meta property="og:image" content="%s">', esc_url($image['url']));
        if (! empty($image['width'])) {
            $tags[] = sprintf('<meta property="og:image:width" content="%d">', (int) $image['width']);
        }
        if (! empty($image['height'])) {
            $tags[] = sprintf('<meta property="og:image:height" content="%d">', (int) $image['height']);
        }
    }

    $tags[] = '<meta name="twitter:card" content="summary_large_image">';
    $tags[] = sprintf('<meta name="twitter:title" content="%s">', esc_attr($title));

    if ($description !== '') {
        $tags[] = sprintf('<meta name="twitter:description" content="%s">', esc_attr($description));
    }

    if (! empty($image['url'])) {
        $tags[] = sprintf('<meta name="twitter:image" content="%s">', esc_url($image['url']));
    }

    echo "\n" . implode("\n", $tags) . "\n";
}

add_action('wp_head', 'church_theme_seo_meta', 5);
