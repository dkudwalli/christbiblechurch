<?php
if (! defined('ABSPATH')) {
    exit;
}

function church_theme_defaults(): array
{
    return [
        'hero_eyebrow' => 'Christ Centered, Expository Preaching',
        'hero_title' => 'Exalting Christ by Making Disciples.',
        'hero_primary_label' => 'Plan Your Visit',
        'hero_primary_url' => '/contact/',
        'service_times' => "Sunday Worship - 10:30 AM\nHome Fellowship - Wednesday 7:30 PM",
        'location_name' => 'Villa 94, Concorde Cupertino, Karuna Nagar, Electronics City Phase 1, Bengaluru 560100',
        'about_summary' => 'We are a community committed to exalt Christ by making disciples through the preaching of God\'s Word. Our gatherings for corporate worship on Sundays and gatherings for fellowship and discipleship during the week are marked by verse-by-verse faithful exposition of God\'s Word, helping us grow in the knowledge of and obedience to Christ.',
        'mission_statement' => 'Exalting Christ by Making Disciples.',
        'contact_phone' => '+91 8806242356 (Daril)',
        'contact_email' => 'christbiblechurch.in@gmail.com',
        'contact_address' => "Villa 94, Concorde Cupertino\nKaruna Nagar, Electronics City Phase 1\nBengaluru, Karnataka 560100",
        'map_embed_url' => 'https://www.google.com/maps?q=Villa+94,+Concorde+Cupertino,+Karuna+Nagar,+Electronics+City+Phase+1,+Bengaluru,+Karnataka+560100&output=embed',
        'latest_sermon_heading' => 'Latest Sermon',
        'contact_form_heading' => 'Send Us a Message',
    ];
}

function church_theme_get_mod(string $key): string
{
    $defaults = church_theme_defaults();

    return (string) get_theme_mod($key, $defaults[$key] ?? '');
}

function church_theme_resolve_url(string $url): string
{
    if ($url === '') {
        return '';
    }

    $path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
    $url_host = (string) wp_parse_url($url, PHP_URL_HOST);
    $site_host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);

    if ($path !== '' && ($url_host === '' || $url_host === $site_host)) {
        if ($path === 'about') {
            return church_theme_get_page_url('about');
        }

        if ($path === 'contact') {
            return church_theme_get_page_url('contact');
        }

        if ($path === 'sermons') {
            return church_theme_get_sermon_archive_url();
        }
    }

    if (str_starts_with($url, '/')) {
        return home_url($url);
    }

    return $url;
}

function church_theme_get_page_url(string $slug): string
{
    $normalized_slug = trim($slug, '/');
    $page = $normalized_slug !== '' ? get_page_by_path($normalized_slug) : null;

    if ($page instanceof WP_Post) {
        $permalink = get_permalink($page);

        if (is_string($permalink) && $permalink !== '') {
            return $permalink;
        }
    }

    if ($normalized_slug !== '') {
        return home_url('/' . $normalized_slug . '/');
    }

    return home_url('/');
}

function church_theme_get_sermon_archive_url(): string
{
    return get_post_type_archive_link('sermon') ?: home_url('/sermons/');
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

function church_theme_split_lines(string $value): array
{
    $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];

    return array_values(array_filter(array_map('trim', $lines)));
}

function church_theme_get_about_doctrines(): array
{
    return [
        [
            'title' => __('The Bible', 'church-theme'),
            'summary' => __('We believe that Scripture is the inspired, infallible, and authoritative Word of God, inerrant in its original manuscripts and sufficient for all matters of faith and practice.', 'church-theme'),
            'references' => __('2 Timothy 3:16-17; 2 Peter 1:21', 'church-theme'),
        ],
        [
            'title' => __('God', 'church-theme'),
            'summary' => __('There is one true and living God who eternally exists in three persons: the Father, the Son, and the Holy Spirit, each fully God and equal in essence.', 'church-theme'),
            'references' => __('Deuteronomy 6:4; Matthew 28:19; 2 Corinthians 13:14', 'church-theme'),
        ],
        [
            'title' => __('The Father', 'church-theme'),
            'summary' => __('The Father is sovereign over all creation, working all things according to His will for His glory and the good of His people.', 'church-theme'),
            'references' => __('Isaiah 46:9-10; Ephesians 1:11', 'church-theme'),
        ],
        [
            'title' => __('The Son', 'church-theme'),
            'summary' => __('Jesus Christ, the eternal Son of God, took on human flesh, lived a sinless life, died as a substitute for sinners, rose bodily from the dead, and reigns as King and Mediator.', 'church-theme'),
            'references' => __('John 1:1,14; 1 Corinthians 15:3-4; Hebrews 7:25', 'church-theme'),
        ],
        [
            'title' => __('The Spirit', 'church-theme'),
            'summary' => __('The Holy Spirit regenerates sinners, convicts of sin, indwells believers, and empowers them to live holy lives.', 'church-theme'),
            'references' => __('John 3:5-6; Titus 3:5; Romans 8:9-14', 'church-theme'),
        ],
        [
            'title' => __('Man', 'church-theme'),
            'summary' => __('Man was directly and immediately created in the image of God but fell into sin through Adam, bringing spiritual death and separation from God. All people are sinners by nature and choice, incapable of saving themselves, and the only hope for mankind is the atoning work of Jesus Christ on the Cross.', 'church-theme'),
            'references' => __('Genesis 1:26-27; Romans 3:23-27; Ephesians 2:1-3; Isaiah 53; Hebrews 9:12', 'church-theme'),
        ],
        [
            'title' => __('Salvation', 'church-theme'),
            'summary' => __('Salvation is by grace alone, through faith alone, in Christ alone. Sinners are justified by faith apart from works and are eternally secure in Christ.', 'church-theme'),
            'references' => __('Ephesians 2:8-9; Romans 5:1; John 10:28', 'church-theme'),
        ],
        [
            'title' => __('The Church', 'church-theme'),
            'summary' => __('The Church is the body of Christ, composed of all true believers. Local churches exist to worship God, preach the gospel, administer the ordinances [Baptism & Communion], and make disciples.', 'church-theme'),
            'references' => __('Matthew 16:18; Ephesians 2:19-22; Acts 2:42', 'church-theme'),
        ],
        [
            'title' => __('The Future', 'church-theme'),
            'summary' => __('Christ will return personally and visibly to judge the living and the dead, establish His kingdom, and bring His people into eternal glory, while the unrepentant face eternal punishment.', 'church-theme'),
            'references' => __('Matthew 25:31-46; 1 Thessalonians 4:16-17; Revelation 20:11-15', 'church-theme'),
        ],
    ];
}

function church_theme_get_about_leadership(): array
{
    return [
        'name' => __('Daril Gona', 'church-theme'),
        'role' => __('Pastor-Teacher', 'church-theme'),
        'highlight' => __('Ordained by Christ Community Church, Daril was sent to plant a new church in Electronic City, Bangalore.', 'church-theme'),
        'paragraphs' => [
            __('Born and raised in a Christian family in Andhra Pradesh, Daril came to know the Lord in his early twenties. After a brief career in the IT industry, his passion to share the gospel led him to join Youth for Christ in Hyderabad, marking the start of his ministry journey.', 'church-theme'),
            __('To deepen his understanding, Daril pursued formal theological training at a Pastoral Training Seminary, Goa. After graduating, he served in a church in Mumbai for two years. During the COVID-19 pandemic, he joined Christ Community Church in Pune, where he faithfully served for four years.', 'church-theme'),
        ],
        'family' => __('Daril Gona is happily married to Sharon Gona, and they are blessed with 2 children - Liora and Jeffrey.', 'church-theme'),
    ];
}

function church_theme_file_version(string $relative_path): ?int
{
    $full_path = get_template_directory() . $relative_path;

    return file_exists($full_path) ? filemtime($full_path) : null;
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

    wp_enqueue_script(
        'church-theme-site',
        get_template_directory_uri() . '/assets/js/site.js',
        [],
        church_theme_file_version('/assets/js/site.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'church_theme_enqueue_assets');

function church_theme_customize_register(WP_Customize_Manager $wp_customize): void
{
    $sections = [
        'church_theme_home' => __('Home Page', 'church-theme'),
        'church_theme_about' => __('About Section', 'church-theme'),
        'church_theme_contact' => __('Contact Details', 'church-theme'),
    ];

    foreach ($sections as $id => $title) {
        $wp_customize->add_section($id, [
            'title' => $title,
            'priority' => 35,
        ]);
    }

    $fields = [
        ['section' => 'church_theme_home', 'id' => 'hero_eyebrow', 'label' => __('Hero Eyebrow', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
        ['section' => 'church_theme_home', 'id' => 'hero_title', 'label' => __('Hero Title', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
        ['section' => 'church_theme_home', 'id' => 'hero_primary_label', 'label' => __('Hero Button Label', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
        ['section' => 'church_theme_home', 'id' => 'hero_primary_url', 'label' => __('Hero Button URL', 'church-theme'), 'type' => 'url', 'sanitize' => 'esc_url_raw'],
        ['section' => 'church_theme_home', 'id' => 'service_times', 'label' => __('Service Times', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_home', 'id' => 'location_name', 'label' => __('Location Label', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
        ['section' => 'church_theme_home', 'id' => 'latest_sermon_heading', 'label' => __('Latest Sermon Heading', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
        ['section' => 'church_theme_about', 'id' => 'about_summary', 'label' => __('About Summary', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_about', 'id' => 'mission_statement', 'label' => __('Mission Statement', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_contact', 'id' => 'contact_phone', 'label' => __('Phone Number', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
        ['section' => 'church_theme_contact', 'id' => 'contact_email', 'label' => __('Contact Email', 'church-theme'), 'type' => 'email', 'sanitize' => 'sanitize_email'],
        ['section' => 'church_theme_contact', 'id' => 'contact_address', 'label' => __('Postal Address', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_contact', 'id' => 'map_embed_url', 'label' => __('Map Embed URL', 'church-theme'), 'type' => 'url', 'sanitize' => 'esc_url_raw'],
        ['section' => 'church_theme_contact', 'id' => 'contact_form_heading', 'label' => __('Contact Form Heading', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
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

function church_theme_fallback_menu(): void
{
    $items = [
        ['label' => __('Home', 'church-theme'), 'url' => home_url('/')],
        ['label' => __('About', 'church-theme'), 'url' => church_theme_get_page_url('about')],
        ['label' => __('Sermons', 'church-theme'), 'url' => church_theme_get_sermon_archive_url()],
        ['label' => __('Contact', 'church-theme'), 'url' => church_theme_get_page_url('contact')],
    ];

    echo '<ul id="primary-menu" class="site-nav__list">';
    foreach ($items as $item) {
        printf(
            '<li class="menu-item"><a href="%s">%s</a></li>',
            esc_url($item['url']),
            esc_html($item['label'])
        );
    }
    echo '</ul>';
}

function church_theme_filter_primary_menu_items(array $items, $args): array
{
    if (! isset($args->theme_location) || $args->theme_location !== 'primary') {
        return $items;
    }

    foreach ($items as $item) {
        $menu_title = sanitize_title((string) $item->title);
        $menu_path = trim((string) wp_parse_url((string) $item->url, PHP_URL_PATH), '/');

        if ($item->object === 'page') {
            $page_slug = (string) get_post_field('post_name', (int) $item->object_id);

            if ($page_slug === 'home') {
                $item->url = home_url('/');
                continue;
            }

            if (in_array($page_slug, ['about', 'contact'], true)) {
                $item->url = church_theme_get_page_url($page_slug);
                continue;
            }
        }

        if ($menu_title === 'about' || $menu_path === 'about') {
            $item->url = church_theme_get_page_url('about');
            continue;
        }

        if ($menu_title === 'contact' || $menu_path === 'contact') {
            $item->url = church_theme_get_page_url('contact');
            continue;
        }

        if ($menu_title === 'sermons' || $menu_path === 'sermons') {
            $item->url = church_theme_get_sermon_archive_url();
        }
    }

    return $items;
}
add_filter('wp_nav_menu_objects', 'church_theme_filter_primary_menu_items', 10, 2);

function church_theme_get_sermon_date(int $post_id): string
{
    $value = (string) get_post_meta($post_id, 'sermon_date', true);

    if ($value === '') {
        return get_the_date('', $post_id);
    }

    $timestamp = strtotime($value);

    return $timestamp ? wp_date(get_option('date_format'), $timestamp) : $value;
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
                'eyebrow' => $term->taxonomy === 'series' ? __('Series', 'church-theme') : __('Speaker', 'church-theme'),
                'title' => $term->name,
                'summary' => $summary,
            ];
        }
    }

    $title = post_type_archive_title('', false);

    return [
        'eyebrow' => __('Sermons', 'church-theme'),
        'title' => $title !== '' ? $title : __('Sermons', 'church-theme'),
        'summary' => __('Browse recent teaching by series, preacher, or keyword.', 'church-theme'),
    ];
}
