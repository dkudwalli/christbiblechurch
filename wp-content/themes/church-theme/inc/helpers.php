<?php
if (! defined('ABSPATH')) {
    exit;
}

function church_theme_defaults(): array
{
    return [
        'hero_title' => 'Welcome to Crossroad South',
        'hero_primary_label' => 'Plan Your Visit',
        'hero_primary_url' => '/contact-us/',
        'welcome_summary' => 'We are a community of Christ-followers from diverse linguistic, geographic, and cultural backgrounds. Corporate worship and small groups are conducted in English, and we are kids, youth, and adult friendly.',
        'service_times' => "Corporate Worship - Sunday 10 am\nWednesday Bible Study at 7:30pm - Online Meeting\nContact Us - Tuesday to Saturday 9:30 am - 5:30 pm",
        'worship_location' => "Mother Theresa Hall, Don Bosco Skill Mission\nNo. 2127/81/2D/1, Kothanur Dinne Road\nBengaluru 560076",
        'communication_address' => "A404, Jankal Pristine, Lake View Rd\nKothnur, Kothannur, 8th Phase\nJ. P. Nagar, Bengaluru 560076",
        'mission_statement' => 'Exalt the Triune God, edify fellow believers, and evangelize the unreached.',
        'footer_mission_line' => "Exalting the Triune God\nEdifying Believers\nEvangelizing the Unreached",
        'vision_statement' => 'To be a platform for individuals and families living in South Bengaluru to meet Jesus Christ and grow in Christian discipleship as a way of life.',
        'core_values_summary' => 'Breaking down barriers, gospel-centered living, deep biblical conviction, and missional engagement.',
        'contact_phone' => '+919663065363',
        'contact_email' => 'crossroadsouthchurch@gmail.com',
        'map_embed_url' => 'https://www.google.com/maps?q=Mother+Theresa+Hall,+Don+Bosco+Skill+Mission,+Kothanur+Dinne+Road,+Bengaluru+560076&output=embed',
        'latest_sermon_heading' => 'Latest Sermon',
        'contact_form_heading' => 'Send Us a Message',
        'instagram_profile_url' => '',
        'instagram_username' => '',
        'instagram_account_id' => '',
        'instagram_access_token' => '',
        'default_og_image' => '',
        'banner_heading' => '',
        'banner_subtext' => '',
        'banner_cta_label' => '',
        'banner_cta_url' => '',
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

function church_theme_get_map_directions_url(): string
{
    $location = implode(', ', church_theme_split_lines(church_theme_get_mod('worship_location')));

    if ($location === '') {
        return '';
    }

    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($location);
}

function church_theme_sanitize_instagram_account_id(string $value): string
{
    return preg_replace('/[^0-9]/', '', $value) ?: '';
}

function church_theme_sanitize_checkbox($value): bool
{
    return (bool) $value;
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

function church_theme_render_rich_text_fragment(string $content): string
{
    $content = trim($content);

    if ($content === '') {
        return '';
    }

    return wpautop(wp_kses_post($content));
}

/**
 * Render a grid of posts from a WP_Query using a card template part.
 *
 * Echoes a wrapper <div class="$wrapper_class"> containing one rendered card
 * per post, then restores the global post. Returns false without echoing
 * anything when the query has no posts, so callers can render their own
 * (bespoke) empty state.
 *
 * @param WP_Query $query        The query to loop over.
 * @param array    $template     [slug, name] passed to get_template_part().
 * @param string   $wrapper_class Class for the wrapping grid <div>.
 */
function church_theme_render_post_grid(WP_Query $query, array $template, string $wrapper_class = ''): bool
{
    if (! $query->have_posts()) {
        return false;
    }

    echo '<div class="' . esc_attr($wrapper_class) . '">';

    while ($query->have_posts()) {
        $query->the_post();
        get_template_part($template[0], $template[1] ?? null);
    }

    wp_reset_postdata();
    echo '</div>';

    return true;
}
