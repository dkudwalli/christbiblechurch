<?php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Return an inline SVG icon from the theme's icon set.
 *
 * Icons share a 24x24 viewBox and the stroke style used across the theme.
 * Supported $args: 'size' (int px, default 16), 'stroke_width' (default 2),
 * 'class' (default 'inline-icon'; pass '' for no class). Returns '' for an
 * unknown name.
 */
function church_theme_icon(string $name, array $args = []): string
{
    $paths = [
        'location' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>',
        'phone'    => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>',
        'envelope' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>',
        'clock'    => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>',
        'address'  => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>',
        'arrow-left'  => '<polyline points="15 18 9 12 15 6"></polyline>',
        'arrow-right' => '<polyline points="9 18 15 12 9 6"></polyline>',
    ];

    if (! isset($paths[$name])) {
        return '';
    }

    $size = (int) ($args['size'] ?? 16);
    $stroke_width = (string) ($args['stroke_width'] ?? '2');
    $class = $args['class'] ?? 'inline-icon';
    $class_attr = $class !== '' ? ' class="' . esc_attr($class) . '"' : '';

    return sprintf(
        '<svg%s width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
        $class_attr,
        $size,
        $size,
        esc_attr($stroke_width),
        $paths[$name]
    );
}
