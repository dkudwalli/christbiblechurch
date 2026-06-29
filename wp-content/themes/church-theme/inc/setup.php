<?php
if (! defined('ABSPATH')) {
    exit;
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
        '/assets/fonts/inter-latin.woff2',
        '/assets/fonts/spacegrotesk-latin.woff2',
    ];
    foreach ($fonts as $font) {
        $url = esc_url(get_template_directory_uri() . $font);
        echo "<link rel=\"preload\" href=\"{$url}\" as=\"font\" type=\"font/woff2\" crossorigin>\n";
    }
}

add_action('wp_head', 'church_theme_preload_fonts', 1);
