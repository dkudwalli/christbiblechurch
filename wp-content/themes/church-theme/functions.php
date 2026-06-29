<?php
if (! defined('ABSPATH')) {
    exit;
}

// Theme functionality is split into focused partials under inc/. Each is required
// here before any hook fires; load order is not significant because functions
// resolve at call time and the partials only define functions + register hooks.
$church_theme_inc = get_template_directory() . '/inc/';
require $church_theme_inc . 'helpers.php';
require $church_theme_inc . 'images.php';
require $church_theme_inc . 'icons.php';
require $church_theme_inc . 'instagram.php';
require $church_theme_inc . 'nav.php';
require $church_theme_inc . 'queries.php';
require $church_theme_inc . 'seo.php';
require $church_theme_inc . 'customizer.php';
require $church_theme_inc . 'setup.php';
