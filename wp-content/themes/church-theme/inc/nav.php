<?php
if (! defined('ABSPATH')) {
    exit;
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

function church_theme_get_photo_album_archive_url(): string
{
    return church_theme_get_page_url('gallery');
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

function church_theme_get_photo_album_url(?int $post_id = null): string
{
    $resolved_post_id = $post_id ?: get_the_ID();
    $permalink = $resolved_post_id > 0 ? get_permalink($resolved_post_id) : '';

    if (is_string($permalink) && $permalink !== '') {
        return $permalink;
    }

    return church_theme_get_photo_album_archive_url();
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
