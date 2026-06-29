<?php
if (! defined('ABSPATH')) {
    exit;
}

function church_theme_get_static_image_variants(string $relative_path, string $full_path): array
{
    $relative_directory = trim((string) dirname($relative_path), '.');
    $filename = pathinfo($full_path, PATHINFO_FILENAME);
    $extension = pathinfo($full_path, PATHINFO_EXTENSION);
    $variants = [];

    foreach (glob(dirname($full_path) . '/' . $filename . '-*.' . $extension) ?: [] as $variant_path) {
        if (! preg_match('/-(\d+)$/', (string) pathinfo($variant_path, PATHINFO_FILENAME), $matches)) {
            continue;
        }

        $dimensions = wp_getimagesize($variant_path);

        if (! is_array($dimensions)) {
            continue;
        }

        $width = (int) ($dimensions[0] ?? 0);
        $height = (int) ($dimensions[1] ?? 0);

        if ($width < 1 || $height < 1) {
            continue;
        }

        $variants[$width] = [
            'src' => get_template_directory_uri() . '/' . trim($relative_directory . '/' . basename($variant_path), '/'),
            'width' => $width,
            'height' => $height,
        ];
    }

    ksort($variants);

    return $variants;
}

function church_theme_build_static_image_srcset(array $variants): string
{
    $candidates = [];

    foreach ($variants as $variant) {
        $src = (string) ($variant['src'] ?? '');
        $width = (int) ($variant['width'] ?? 0);

        if ($src === '' || $width < 1) {
            continue;
        }

        $candidates[] = sprintf('%s %dw', esc_url($src), $width);
    }

    return implode(', ', $candidates);
}

function church_theme_get_static_image(
    string $relative_path,
    string $alt,
    string $caption = '',
    ?int $width = null,
    ?int $height = null,
    ?string $object_position = null
): ?array
{
    $full_path = get_template_directory() . $relative_path;

    if (! file_exists($full_path)) {
        return null;
    }

    $dimensions = wp_getimagesize($full_path);
    $resolved_width = $width;
    $resolved_height = $height;

    if (is_array($dimensions)) {
        $resolved_width = $resolved_width ?: (int) ($dimensions[0] ?? 0);
        $resolved_height = $resolved_height ?: (int) ($dimensions[1] ?? 0);
    }

    $variants = church_theme_get_static_image_variants($relative_path, $full_path);

    if (($resolved_width ?? 0) > 0 && ($resolved_height ?? 0) > 0) {
        $variants[(int) $resolved_width] = [
            'src' => get_template_directory_uri() . $relative_path,
            'width' => (int) $resolved_width,
            'height' => (int) $resolved_height,
        ];
        ksort($variants);
    }

    return [
        'src' => get_template_directory_uri() . $relative_path,
        'alt' => $alt,
        'caption' => $caption,
        'width' => $resolved_width,
        'height' => $resolved_height,
        'object_position' => $object_position,
        'srcset' => church_theme_build_static_image_srcset($variants),
        'variants' => $variants,
    ];
}

function church_theme_render_html_attributes(array $attributes): string
{
    $parts = [];

    foreach ($attributes as $name => $value) {
        if ($value === null || $value === false || $value === '') {
            continue;
        }

        if ($value === true) {
            $parts[] = sanitize_key((string) $name);
            continue;
        }

        $parts[] = sprintf(
            '%s="%s"',
            sanitize_key((string) $name),
            esc_attr((string) $value)
        );
    }

    return implode(' ', $parts);
}

function church_theme_render_static_image(?array $image, array $attributes = []): string
{
    if (! is_array($image)) {
        return '';
    }

    $style = trim((string) ($attributes['style'] ?? ''));
    $object_position = trim((string) ($attributes['object_position'] ?? ($image['object_position'] ?? '')));

    if ($object_position !== '') {
        $style = trim($style . ($style !== '' ? '; ' : '') . 'object-position: ' . $object_position . ';');
    }

    $default_attributes = [
        'src' => (string) ($image['src'] ?? ''),
        'alt' => (string) ($image['alt'] ?? ''),
        'width' => (int) ($image['width'] ?? 0) > 0 ? (int) $image['width'] : null,
        'height' => (int) ($image['height'] ?? 0) > 0 ? (int) $image['height'] : null,
        'loading' => 'lazy',
        'decoding' => 'async',
        'srcset' => (string) ($image['srcset'] ?? ''),
        'sizes' => null,
        'fetchpriority' => null,
        'style' => $style,
    ];
    $resolved_attributes = array_merge($default_attributes, $attributes);

    unset($resolved_attributes['object_position']);

    return sprintf('<img %s>', church_theme_render_html_attributes($resolved_attributes));
}

function church_theme_get_brand_logo_asset(): array
{
    $logo = church_theme_get_static_image(
        '/assets/images/crossroads/crossroads-logo.webp',
        get_bloginfo('name') . ' logo',
        '',
        1300,
        594
    );

    if (is_array($logo)) {
        return $logo;
    }

    return [
        'src' => get_template_directory_uri() . '/assets/images/logo-fav.svg',
        'alt' => get_bloginfo('name') . ' logo',
        'caption' => '',
        'width' => 56,
        'height' => 56,
    ];
}

function church_theme_get_attachment_image_asset(int $attachment_id): ?array
{
    if ($attachment_id < 1) {
        return null;
    }

    $image = wp_get_attachment_image_src($attachment_id, 'full');

    if (! is_array($image)) {
        return null;
    }

    $attachment = get_post($attachment_id);
    $alt = trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true));
    $caption = $attachment instanceof WP_Post ? trim((string) $attachment->post_excerpt) : '';

    return [
        'src' => (string) $image[0],
        'alt' => $alt !== '' ? $alt : get_the_title($attachment_id),
        'caption' => $caption,
        'width' => (int) ($image[1] ?? 0),
        'height' => (int) ($image[2] ?? 0),
        'srcset' => (string) (wp_get_attachment_image_srcset($attachment_id, 'full') ?: ''),
    ];
}

/**
 * Resolve the front-page hero community image (shown above the "Gather With Us"
 * card). Returns the Customizer-selected attachment when set and valid, otherwise
 * the bundled theme asset. Both shapes are compatible with
 * church_theme_render_static_image().
 *
 * @return array<string, mixed>|null
 */
function church_theme_get_hero_community_image(): ?array
{
    $attachment_id = (int) get_theme_mod('hero_community_image', 0);

    if ($attachment_id > 0) {
        $asset = church_theme_get_attachment_image_asset($attachment_id);

        if ($asset !== null) {
            // Guard against an alt regression: church_theme_get_attachment_image_asset()
            // falls back to the attachment title (= filename stem, e.g. "women-ministry-1")
            // when the Media Library "Alternative Text" field is empty. Substitute a
            // meaningful generic description so the hero never ships a filename as alt.
            if (trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true)) === '') {
                $asset['alt'] = __('The Crossroad South Church community', 'church-theme');
            }

            return $asset;
        }
    }

    return church_theme_get_static_image(
        '/assets/images/crossroads/retreat.webp',
        __('The Crossroad South Church community at a recent retreat', 'church-theme')
    );
}

/**
 * Resolve the hero banner image slots (banner_image_1..5) into ordered image
 * assets. Skips empty/invalid slots and de-duplicates repeated attachments.
 *
 * Each asset carries its attachment 'id' so the template can render it via
 * wp_get_attachment_image() — necessary because skipping/de-duping means the
 * array index no longer maps back to the original slot number.
 *
 * @return array<int, array<string, mixed>>
 */
function church_theme_get_banner_images(): array
{
    $images = [];
    $seen = [];

    for ($slot = 1; $slot <= 5; $slot++) {
        $attachment_id = (int) get_theme_mod('banner_image_' . $slot, 0);

        if ($attachment_id < 1 || isset($seen[$attachment_id])) {
            continue;
        }

        $asset = church_theme_get_attachment_image_asset($attachment_id);

        if ($asset === null) {
            continue;
        }

        $asset['id'] = $attachment_id;
        $images[] = $asset;
        $seen[$attachment_id] = true;
    }

    return $images;
}

/**
 * Resolve the hero banner video slot (banner_video) into a renderable source,
 * or null when unset/invalid. The poster falls back to the first banner image
 * so something paints before the video bytes arrive.
 *
 * @return array<string, string>|null
 */
function church_theme_get_banner_video(): ?array
{
    $attachment_id = (int) get_theme_mod('banner_video', 0);

    if ($attachment_id < 1) {
        return null;
    }

    $url = wp_get_attachment_url($attachment_id);

    if (! $url) {
        return null;
    }

    $images = church_theme_get_banner_images();

    return [
        'url' => $url,
        'mime' => get_post_mime_type($attachment_id) ?: 'video/mp4',
        'poster' => $images !== [] ? (string) $images[0]['src'] : '',
    ];
}

/**
 * Resolve a sermon card's thumbnail image: a per-sermon featured image when set
 * (lets the church differentiate cards), otherwise the YouTube thumbnail.
 *
 * Returns ['src', 'width', 'height', 'srcset'] or null. For display in the
 * sermon card grid only — single-sermon and front-page render the video player.
 */
function church_theme_get_sermon_card_image(int $post_id): ?array
{
    if ($post_id > 0 && has_post_thumbnail($post_id)) {
        $asset = church_theme_get_attachment_image_asset((int) get_post_thumbnail_id($post_id));
        if (is_array($asset) && ! empty($asset['src'])) {
            return [
                'src' => (string) $asset['src'],
                'width' => (int) ($asset['width'] ?? 0),
                'height' => (int) ($asset['height'] ?? 0),
                'srcset' => (string) ($asset['srcset'] ?? ''),
            ];
        }
    }

    $video_id = (string) get_post_meta($post_id, 'youtube_video_id', true);
    if ($video_id !== '') {
        return [
            'src' => 'https://i.ytimg.com/vi/' . rawurlencode($video_id) . '/hqdefault.jpg',
            'width' => 480,
            'height' => 360,
            'srcset' => '',
        ];
    }

    return null;
}

function church_theme_get_page_section_featured_image(WP_Post $section): ?array
{
    return church_theme_get_attachment_image_asset((int) get_post_thumbnail_id($section->ID));
}

function church_theme_get_gallery_feature_media(): ?array
{
    return church_theme_get_static_image(
        '/assets/images/crossroads/retreat.webp',
        'Crossroad South Church retreat gathering',
        '',
        1300,
        975
    );
}
