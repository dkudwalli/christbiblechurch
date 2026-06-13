<?php
if (! defined('ABSPATH')) {
    exit;
}

function church_theme_defaults(): array
{
    return [
        'hero_title' => 'Exalting the Triune God, Edifying Believers, Evangelizing the Unreached.',
        'hero_primary_label' => 'Plan Your Visit',
        'hero_primary_url' => '/contact-us/',
        'welcome_summary' => 'We are a community of Christ-followers from diverse linguistic, geographic, and cultural backgrounds. Corporate worship and small groups are conducted in English, and we are kids, youth, and adult friendly.',
        'service_times' => "Corporate Worship - Sunday 10 am\nWednesday Bible Study at 7:30pm - Online Meeting\nOffice Hours - Tuesday to Saturday 9:30 am - 5:30 pm",
        'worship_location' => "Mother Theresa Hall, Don Bosco Skill Mission\nNo. 2127/81/2D/1, Kothanur Dinne Road\nBengaluru 560076",
        'communication_address' => "A404, Jankal Pristine, Lake View Rd\nKothnur, Kothannur, 8th Phase\nJ. P. Nagar, Bengaluru 560076",
        'mission_statement' => 'Exalt the Triune God, edify fellow believers, and evangelize the unreached.',
        'footer_mission_line' => "Exalting the Triune God.\nEdifying Believers.\nEvangelizing the Unreached.",
        'vision_statement' => 'To be a platform for individuals and families living in South Bengaluru to meet Jesus Christ and grow in Christian discipleship as a way of life.',
        'core_values_summary' => 'Breaking down barriers, gospel-centered living, deep biblical conviction, and missional engagement.',
        'footer_invite' => '',
        'contact_phone' => '+919663065363',
        'contact_email' => 'crossroadsouthchurch@gmail.com',
        'map_embed_url' => 'https://www.google.com/maps?q=Mother+Theresa+Hall,+Don+Bosco+Skill+Mission,+Kothanur+Dinne+Road,+Bengaluru+560076&output=embed',
        'latest_sermon_heading' => 'Latest Sermon',
        'contact_form_heading' => 'Send Us a Message',
        'instagram_profile_url' => '',
        'instagram_username' => '',
        'instagram_account_id' => '',
        'instagram_access_token' => '',
    ];
}

function church_theme_get_mod(string $key): string
{
    $defaults = church_theme_defaults();

    return (string) get_theme_mod($key, $defaults[$key] ?? '');
}

function church_theme_split_lines(string $value): array
{
    $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];

    return array_values(array_filter(array_map('trim', $lines)));
}

function church_theme_phone_href(string $value): string
{
    return preg_replace('/[^0-9+]/', '', $value) ?: '';
}

function church_theme_get_instagram_profile_url(): string
{
    $profile_url = trim(church_theme_get_mod('instagram_profile_url'));

    if ($profile_url !== '') {
        return $profile_url;
    }

    $username = ltrim(trim(church_theme_get_mod('instagram_username')), '@');

    if ($username === '') {
        return '';
    }

    return 'https://www.instagram.com/' . rawurlencode($username) . '/';
}

function church_theme_sanitize_instagram_account_id(string $value): string
{
    return preg_replace('/[^0-9]/', '', $value) ?: '';
}

function church_theme_normalize_path(string $path): string
{
    $normalized = '/' . trim($path, '/');

    return $normalized === '/' ? '/' : untrailingslashit($normalized);
}

function church_theme_get_current_request_path(): string
{
    global $wp;

    $request = isset($wp->request) ? (string) $wp->request : '';

    return $request === '' ? '/' : church_theme_normalize_path($request);
}

function church_theme_is_current_page_section_link(string $url, string $current_path): bool
{
    $parts = wp_parse_url($url);

    if (! is_array($parts) || empty($parts['fragment'])) {
        return false;
    }

    $url_host = (string) ($parts['host'] ?? '');
    $site_host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);

    if ($url_host !== '' && $url_host !== $site_host) {
        return false;
    }

    $link_path = church_theme_normalize_path((string) ($parts['path'] ?? '/'));

    return $link_path === $current_path;
}

function church_theme_clear_current_menu_state(object $item): void
{
    $current_classes = [
        'current-menu-item',
        'current_page_item',
        'current-menu-parent',
        'current_page_parent',
        'current-menu-ancestor',
        'current_page_ancestor',
    ];

    $item->classes = array_values(array_filter(
        (array) $item->classes,
        static fn ($class): bool => ! in_array($class, $current_classes, true)
    ));
    $item->current = false;
    $item->current_item_parent = false;
    $item->current_item_ancestor = false;
}

function church_theme_get_instagram_media_endpoint(string $account_id): string
{
    return sprintf('https://graph.instagram.com/v25.0/%s/media', rawurlencode($account_id));
}

function church_theme_normalize_instagram_media_item(array $item): ?array
{
    $media_type = sanitize_text_field((string) ($item['media_type'] ?? ''));
    $image_url = '';

    if ($media_type === 'VIDEO') {
        $image_url = (string) ($item['thumbnail_url'] ?? $item['media_url'] ?? '');
    } elseif ($media_type === 'CAROUSEL_ALBUM') {
        $children = $item['children']['data'][0] ?? [];
        $image_url = (string) ($item['thumbnail_url'] ?? $children['media_url'] ?? $children['thumbnail_url'] ?? $item['media_url'] ?? '');
    } else {
        $image_url = (string) ($item['media_url'] ?? $item['thumbnail_url'] ?? '');
    }

    $permalink = (string) ($item['permalink'] ?? '');

    if ($image_url === '' || $permalink === '') {
        return null;
    }

    return [
        'id' => sanitize_text_field((string) ($item['id'] ?? '')),
        'caption' => sanitize_textarea_field(wp_strip_all_tags((string) ($item['caption'] ?? ''))),
        'image_url' => esc_url_raw($image_url),
        'permalink' => esc_url_raw($permalink),
        'timestamp' => sanitize_text_field((string) ($item['timestamp'] ?? '')),
        'media_type' => $media_type,
    ];
}

function church_theme_get_instagram_feed(int $limit = 9): array
{
    $account_id = church_theme_sanitize_instagram_account_id(church_theme_get_mod('instagram_account_id'));
    $access_token = trim(church_theme_get_mod('instagram_access_token'));
    $fallback = [
        'configured' => false,
        'items' => [],
        'error' => false,
    ];

    if ($account_id === '' || $access_token === '') {
        return $fallback;
    }

    $transient_key = 'church_theme_ig_' . md5($account_id . '|' . $access_token . '|' . $limit);
    $cached = get_transient($transient_key);

    if (is_array($cached)) {
        return $cached;
    }

    $endpoint = add_query_arg([
        'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,children{media_type,media_url,thumbnail_url}',
        'limit' => max(1, $limit),
        'access_token' => $access_token,
    ], church_theme_get_instagram_media_endpoint($account_id));
    $response = wp_safe_remote_get($endpoint, [
        'timeout' => 15,
    ]);
    $response_code = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);

    if (is_wp_error($response) || $response_code < 200 || $response_code >= 300) {
        $result = [
            'configured' => true,
            'items' => [],
            'error' => true,
        ];
        set_transient($transient_key, $result, 5 * MINUTE_IN_SECONDS);

        return $result;
    }

    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    $items = [];

    if (! is_array($body['data'] ?? null)) {
        $result = [
            'configured' => true,
            'items' => [],
            'error' => true,
        ];
        set_transient($transient_key, $result, 5 * MINUTE_IN_SECONDS);

        return $result;
    }

    if (is_array($body['data'])) {
        foreach ($body['data'] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $normalized = church_theme_normalize_instagram_media_item($item);

            if (is_array($normalized)) {
                $items[] = $normalized;
            }
        }
    }

    $result = [
        'configured' => true,
        'items' => $items,
        'error' => false,
    ];
    set_transient($transient_key, $result, 15 * MINUTE_IN_SECONDS);

    return $result;
}

function church_theme_named_pages(): array
{
    return [
        'home' => 'home',
        'about' => 'about-us',
        'about-us' => 'about-us',
        'events' => 'events',
        'worship' => 'worship',
        'gallery' => 'gallery',
        'give' => 'give',
        'contact' => 'contact-us',
        'contact-us' => 'contact-us',
        'about-us.html' => 'about-us',
        'events.html' => 'events',
        'worship.html' => 'worship',
        'gallery.html' => 'gallery',
        'give.html' => 'give',
        'contact-us.html' => 'contact-us',
    ];
}

function church_theme_get_page_by_paths(array $paths): ?WP_Post
{
    foreach ($paths as $path) {
        $normalized_path = trim($path, '/');

        if ($normalized_path === '') {
            continue;
        }

        $page = get_page_by_path($normalized_path);

        if ($page instanceof WP_Post) {
            return $page;
        }
    }

    return null;
}

function church_theme_get_page_url(string $slug): string
{
    $normalized_slug = trim($slug, '/');
    $named_pages = church_theme_named_pages();

    if ($normalized_slug === '' || $normalized_slug === 'home') {
        return home_url('/');
    }

    if ($normalized_slug === 'sermons') {
        return church_theme_get_sermon_archive_url();
    }

    if ($normalized_slug === 'events') {
        return church_theme_get_event_archive_url();
    }

    $resolved_slug = $named_pages[$normalized_slug] ?? $normalized_slug;
    $page = church_theme_get_page_by_paths([$resolved_slug]);

    if ($page instanceof WP_Post) {
        $permalink = get_permalink($page);

        if (is_string($permalink) && $permalink !== '') {
            return $permalink;
        }
    }

    return home_url('/' . $resolved_slug . '/');
}

function church_theme_get_sermon_archive_url(): string
{
    return get_post_type_archive_link('sermon') ?: home_url('/sermons/');
}

function church_theme_get_event_archive_url(): string
{
    return get_post_type_archive_link('event') ?: home_url('/events/');
}

function church_theme_get_sermon_url(?int $post_id = null): string
{
    $resolved_post_id = $post_id ?: get_the_ID();
    $permalink = $resolved_post_id > 0 ? get_permalink($resolved_post_id) : '';

    if (is_string($permalink) && $permalink !== '') {
        return $permalink;
    }

    return church_theme_get_sermon_archive_url();
}

function church_theme_get_event_url(?int $post_id = null): string
{
    $resolved_post_id = $post_id ?: get_the_ID();
    $permalink = $resolved_post_id > 0 ? get_permalink($resolved_post_id) : '';

    if (is_string($permalink) && $permalink !== '') {
        return $permalink;
    }

    return church_theme_get_event_archive_url();
}

function church_theme_resolve_url(string $url): string
{
    if ($url === '' || str_starts_with($url, '#')) {
        return $url;
    }

    $parts = wp_parse_url($url);

    if (! is_array($parts)) {
        return $url;
    }

    $path = trim((string) ($parts['path'] ?? ''), '/');
    $url_host = (string) ($parts['host'] ?? '');
    $site_host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
    $query = isset($parts['query']) ? '?' . $parts['query'] : '';
    $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
    $named_pages = church_theme_named_pages();

    if ($path !== '' && ($url_host === '' || $url_host === $site_host)) {
        if (isset($named_pages[$path])) {
            return church_theme_get_page_url($named_pages[$path]) . $query . $fragment;
        }

        if ($path === 'sermons' || $path === 'sermons.html') {
            return church_theme_get_sermon_archive_url() . $query . $fragment;
        }

        if ($path === 'events' || $path === 'events.html') {
            return church_theme_get_event_archive_url() . $query . $fragment;
        }
    }

    if (str_starts_with($url, '/')) {
        return home_url($path === '' ? '/' : '/' . $path . '/') . $query . $fragment;
    }

    return $url;
}

function church_theme_get_section_anchor(WP_Post $section): string
{
    return sanitize_title($section->post_name ?: $section->post_title);
}

function church_theme_get_child_sections(int $parent_id): array
{
    $sections = get_pages([
        'parent' => $parent_id,
        'sort_column' => 'menu_order,post_title',
        'post_status' => 'publish',
    ]);

    return array_values(array_filter($sections, static fn ($section): bool => $section instanceof WP_Post));
}

function church_theme_file_version(string $relative_path): ?int
{
    $full_path = get_template_directory() . $relative_path;

    return file_exists($full_path) ? filemtime($full_path) : null;
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

function church_theme_get_page_section_featured_image(WP_Post $section): ?array
{
    return church_theme_get_attachment_image_asset((int) get_post_thumbnail_id($section->ID));
}

function church_theme_render_rich_text_fragment(string $content): string
{
    $content = trim($content);

    if ($content === '') {
        return '';
    }

    return wpautop(wp_kses_post($content));
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

function church_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo');
    add_theme_support('align-wide');
    add_editor_style('assets/css/site.css');

    register_nav_menus([
        'primary' => __('Primary Navigation', 'church-theme'),
    ]);
}
add_action('after_setup_theme', 'church_theme_setup');

function church_theme_enqueue_assets(): void
{
    wp_enqueue_style('church-theme-core', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
    wp_enqueue_style(
        'church-theme-site',
        get_template_directory_uri() . '/assets/css/site.css',
        ['church-theme-core'],
        church_theme_file_version('/assets/css/site.css')
    );
    wp_enqueue_style(
        'church-theme-accessibility',
        get_template_directory_uri() . '/assets/css/accessibility.css',
        ['church-theme-site'],
        church_theme_file_version('/assets/css/accessibility.css')
    );
    wp_enqueue_style(
        'church-theme-forms',
        get_template_directory_uri() . '/assets/css/forms.css',
        ['church-theme-accessibility'],
        church_theme_file_version('/assets/css/forms.css')
    );

    wp_enqueue_script(
        'church-theme-site',
        get_template_directory_uri() . '/assets/js/site.js',
        [],
        church_theme_file_version('/assets/js/site.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'church_theme_enqueue_assets');

function church_theme_preload_fonts(): void
{
    $fonts = [
        '/assets/fonts/dmsans-latin.woff2',
        '/assets/fonts/lora-latin.woff2',
    ];
    foreach ($fonts as $font) {
        $url = esc_url(get_template_directory_uri() . $font);
        echo "<link rel=\"preload\" href=\"{$url}\" as=\"font\" type=\"font/woff2\" crossorigin>\n";
    }
}
add_action('wp_head', 'church_theme_preload_fonts', 1);

function church_theme_customize_register(WP_Customize_Manager $wp_customize): void
{
    $sections = [
        'church_theme_home' => __('Home Page', 'church-theme'),
        'church_theme_identity' => __('Church Identity', 'church-theme'),
        'church_theme_contact' => __('Contact Details', 'church-theme'),
        'church_theme_gallery' => __('Gallery / Instagram', 'church-theme'),
    ];

    foreach ($sections as $id => $title) {
        $wp_customize->add_section($id, [
            'title' => $title,
            'priority' => 35,
        ]);
    }

    $fields = [
        ['section' => 'church_theme_home', 'id' => 'hero_title', 'label' => __('Hero Title', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
        ['section' => 'church_theme_home', 'id' => 'hero_primary_label', 'label' => __('Hero Button Label', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
        ['section' => 'church_theme_home', 'id' => 'hero_primary_url', 'label' => __('Hero Button URL', 'church-theme'), 'type' => 'url', 'sanitize' => 'esc_url_raw'],
        ['section' => 'church_theme_home', 'id' => 'welcome_summary', 'label' => __('Welcome Summary', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_home', 'id' => 'service_times', 'label' => __('Service Times', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_home', 'id' => 'worship_location', 'label' => __('Worship Location', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_home', 'id' => 'latest_sermon_heading', 'label' => __('Latest Sermon Heading', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
        ['section' => 'church_theme_identity', 'id' => 'mission_statement', 'label' => __('Mission Statement', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_identity', 'id' => 'footer_mission_line', 'label' => __('Footer Mission Line', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_identity', 'id' => 'vision_statement', 'label' => __('Vision Statement', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_identity', 'id' => 'core_values_summary', 'label' => __('Core Values Summary', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_identity', 'id' => 'footer_invite', 'label' => __('Footer Invite Copy', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_contact', 'id' => 'contact_phone', 'label' => __('Phone Number', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
        ['section' => 'church_theme_contact', 'id' => 'contact_email', 'label' => __('Contact Email', 'church-theme'), 'type' => 'email', 'sanitize' => 'sanitize_email'],
        ['section' => 'church_theme_contact', 'id' => 'communication_address', 'label' => __('Communication Address', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_contact', 'id' => 'map_embed_url', 'label' => __('Map Embed URL', 'church-theme'), 'type' => 'url', 'sanitize' => 'esc_url_raw'],
        ['section' => 'church_theme_contact', 'id' => 'contact_form_heading', 'label' => __('Contact Form Heading', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
        ['section' => 'church_theme_gallery', 'id' => 'instagram_profile_url', 'label' => __('Instagram Profile URL', 'church-theme'), 'type' => 'url', 'sanitize' => 'esc_url_raw'],
        ['section' => 'church_theme_gallery', 'id' => 'instagram_username', 'label' => __('Instagram Username', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
        ['section' => 'church_theme_gallery', 'id' => 'instagram_account_id', 'label' => __('Instagram Account ID', 'church-theme'), 'type' => 'text', 'sanitize' => 'church_theme_sanitize_instagram_account_id'],
        ['section' => 'church_theme_gallery', 'id' => 'instagram_access_token', 'label' => __('Instagram Access Token', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
    ];

    foreach ($fields as $field) {
        $wp_customize->add_setting($field['id'], [
            'default' => church_theme_defaults()[$field['id']] ?? '',
            'sanitize_callback' => $field['sanitize'],
        ]);

        $wp_customize->add_control($field['id'], [
            'section' => $field['section'],
            'label' => $field['label'],
            'type' => $field['type'],
        ]);
    }
}
add_action('customize_register', 'church_theme_customize_register');

function church_theme_get_primary_nav_items(): array
{
    $about_sections = church_theme_get_page_section_nav_items('about-us');
    $worship_sections = church_theme_get_page_section_nav_items('worship');

    return [
        [
            'label' => __('Home', 'church-theme'),
            'url' => home_url('/'),
        ],
        [
            'label' => __('About Us', 'church-theme'),
            'url' => church_theme_get_page_url('about-us'),
            'children' => $about_sections,
        ],
        [
            'label' => __('Worship', 'church-theme'),
            'url' => church_theme_get_page_url('worship'),
            'children' => $worship_sections,
        ],
        [
            'label' => __('Gallery', 'church-theme'),
            'url' => church_theme_get_page_url('gallery'),
        ],
        [
            'label' => __('Give', 'church-theme'),
            'url' => church_theme_get_page_url('give'),
        ],
        [
            'label' => __('Sermons', 'church-theme'),
            'url' => church_theme_get_sermon_archive_url(),
        ],
        [
            'label' => __('Contact Us', 'church-theme'),
            'url' => church_theme_get_page_url('contact-us'),
        ],
    ];
}

function church_theme_get_page_section_nav_items(string $page_slug): array
{
    $page = church_theme_get_page_by_paths([$page_slug]);

    if (! $page instanceof WP_Post) {
        return [];
    }

    $items = [];

    foreach (church_theme_get_child_sections($page->ID) as $section) {
        $items[] = [
            'label' => get_the_title($section),
            'url' => church_theme_get_page_url($page_slug) . '#' . church_theme_get_section_anchor($section),
        ];
    }

    return $items;
}

function church_theme_fallback_menu(): void
{
    $items = church_theme_get_primary_nav_items();

    echo '<ul id="primary-menu" class="site-nav__list">';

    foreach ($items as $item) {
        $children = $item['children'] ?? [];
        $has_children = $children !== [];

        printf(
            '<li class="menu-item%s"><a href="%s">%s</a>',
            $has_children ? ' menu-item-has-children' : '',
            esc_url($item['url']),
            esc_html($item['label'])
        );

        if ($has_children) {
            echo '<ul class="sub-menu">';

            foreach ($children as $child) {
                printf(
                    '<li class="menu-item"><a href="%s">%s</a></li>',
                    esc_url($child['url']),
                    esc_html($child['label'])
                );
            }

            echo '</ul>';
        }

        echo '</li>';
    }

    echo '</ul>';
}

function church_theme_filter_primary_menu_items(array $items, $args): array
{
    if (! isset($args->theme_location) || $args->theme_location !== 'primary') {
        return $items;
    }

    $current_path = church_theme_get_current_request_path();

    foreach ($items as $item) {
        $item->url = church_theme_resolve_url((string) $item->url);

        if (church_theme_is_current_page_section_link((string) $item->url, $current_path)) {
            church_theme_clear_current_menu_state($item);
        }
    }

    return $items;
}
add_filter('wp_nav_menu_objects', 'church_theme_filter_primary_menu_items', 10, 2);

function church_theme_filter_primary_menu_link_attributes(array $atts, $item, $args): array
{
    if (! isset($args->theme_location) || $args->theme_location !== 'primary') {
        return $atts;
    }

    if (church_theme_is_current_page_section_link((string) $item->url, church_theme_get_current_request_path())) {
        unset($atts['aria-current']);
    }

    return $atts;
}
add_filter('nav_menu_link_attributes', 'church_theme_filter_primary_menu_link_attributes', 10, 3);

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

    if ($value === '') {
        return null;
    }

    $timezone = wp_timezone();
    $formats = [
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d\TH:i',
    ];

    foreach ($formats as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $value, $timezone);

        if (! $date instanceof DateTimeImmutable) {
            continue;
        }

        $errors = DateTimeImmutable::getLastErrors();

        if (! is_array($errors) || (($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0)) {
            return $date;
        }
    }

    $timestamp = strtotime($value);

    if (! $timestamp) {
        return null;
    }

    return (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone);
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

function church_theme_get_event_notes_preview(int $post_id, int $word_limit = 26): string
{
    $content = (string) get_post_field('post_content', $post_id);
    $content = wp_strip_all_tags(strip_shortcodes($content));
    $content = trim(preg_replace('/\s+/', ' ', $content) ?: '');

    if ($content === '') {
        return '';
    }

    return wp_trim_words($content, $word_limit);
}

function church_theme_get_sermon_excerpt_preview(int $post_id, int $word_limit = 24): string
{
    $content = (string) get_post_field('post_excerpt', $post_id);

    if ($content === '') {
        $content = wp_strip_all_tags(strip_shortcodes((string) get_post_field('post_content', $post_id)));
    }

    $content = trim(preg_replace('/\s+/', ' ', $content) ?: '');

    if ($content === '') {
        return '';
    }

    return wp_trim_words($content, $word_limit);
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

function church_theme_get_latest_sermon_query(int $limit = 1): WP_Query
{
    return new WP_Query([
        'post_type' => 'sermon',
        'posts_per_page' => $limit,
        'meta_key' => 'sermon_date',
        'orderby' => 'meta_value',
        'order' => 'DESC',
        'no_found_rows' => true,
    ]);
}

function church_theme_get_related_sermon_query(int $post_id, int $limit = 3): array
{
    $series_term = church_theme_get_sermon_primary_term($post_id, 'series');
    $base_args = [
        'post_type' => 'sermon',
        'posts_per_page' => $limit,
        'post__not_in' => [$post_id],
        'meta_key' => 'sermon_date',
        'orderby' => 'meta_value',
        'order' => 'DESC',
        'no_found_rows' => true,
    ];

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
