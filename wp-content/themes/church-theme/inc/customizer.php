<?php
if (! defined('ABSPATH')) {
    exit;
}

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
        ['section' => 'church_theme_home', 'id' => 'banner_heading', 'label' => __('Banner Heading', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
        ['section' => 'church_theme_home', 'id' => 'banner_subtext', 'label' => __('Banner Subtext', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_home', 'id' => 'banner_cta_label', 'label' => __('Banner Button Label', 'church-theme'), 'type' => 'text', 'sanitize' => 'sanitize_text_field'],
        ['section' => 'church_theme_home', 'id' => 'banner_cta_url', 'label' => __('Banner Button URL', 'church-theme'), 'type' => 'url', 'sanitize' => 'esc_url_raw'],
        ['section' => 'church_theme_identity', 'id' => 'mission_statement', 'label' => __('Mission Statement', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_identity', 'id' => 'footer_mission_line', 'label' => __('Footer Mission Line', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_identity', 'id' => 'vision_statement', 'label' => __('Vision Statement', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_identity', 'id' => 'core_values_summary', 'label' => __('Core Values Summary', 'church-theme'), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
        ['section' => 'church_theme_identity', 'id' => 'default_og_image', 'label' => __('Default Share Image URL (Open Graph, ~1200×630 JPG/PNG)', 'church-theme'), 'type' => 'url', 'sanitize' => 'esc_url_raw'],
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

    $defaults = church_theme_defaults();

    foreach ($fields as $field) {
        $wp_customize->add_setting($field['id'], [
            'default' => $defaults[$field['id']] ?? '',
            'sanitize_callback' => $field['sanitize'],
        ]);

        $wp_customize->add_control($field['id'], [
            'section' => $field['section'],
            'label' => $field['label'],
            'type' => $field['type'],
        ]);
    }

    // Hero banner toggle + media slots. These need an explicit block: the loop
    // above only passes a string control type, but media slots require a
    // WP_Customize_Media_Control object and the toggle a boolean default that
    // the string-keyed church_theme_defaults() map can't express.
    $wp_customize->add_setting('banner_enabled', [
        'default' => false,
        'sanitize_callback' => 'church_theme_sanitize_checkbox',
    ]);
    $wp_customize->add_control('banner_enabled', [
        'section' => 'church_theme_home',
        'type' => 'checkbox',
        'priority' => 1,
        'label' => __('Enable hero banner (shows above the main hero)', 'church-theme'),
    ]);

    $wp_customize->add_setting('hero_community_image', [
        'default' => 0,
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'hero_community_image', [
        'section' => 'church_theme_home',
        'mime_type' => 'image',
        'label' => __('Hero Image (beside the welcome text)', 'church-theme'),
        'description' => __('Shown above the “Gather With Us” card. Leave empty to use the bundled default. Recommended ~1300×975 or larger; it is cropped to a 3:2 landscape.', 'church-theme'),
    ]));

    $wp_customize->add_setting('banner_video', [
        'default' => 0,
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'banner_video', [
        'section' => 'church_theme_home',
        'mime_type' => 'video',
        'label' => __('Banner Background Video', 'church-theme'),
        'description' => __('When set, a muted looping video replaces the images. MP4/WebM, ideally under ~5MB.', 'church-theme'),
    ]));

    for ($slot = 1; $slot <= 5; $slot++) {
        $image_id = 'banner_image_' . $slot;

        $wp_customize->add_setting($image_id, [
            'default' => 0,
            'sanitize_callback' => 'absint',
        ]);
        $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, $image_id, [
            'section' => 'church_theme_home',
            'mime_type' => 'image',
            /* translators: %d: banner image slot number. */
            'label' => sprintf(__('Banner Image %d', 'church-theme'), $slot),
        ]));
    }
}

add_action('customize_register', 'church_theme_customize_register');
