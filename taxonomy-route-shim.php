<?php

if (! function_exists('church_route_shim_boot_taxonomy')) {
    function church_route_shim_boot_taxonomy(string $taxonomy, string $slug, int $paged = 1): void
    {
        $query_vars = [$taxonomy => $slug];

        if (! defined('WP_USE_THEMES')) {
            define('WP_USE_THEMES', true);
        }

        $_GET[$taxonomy] = $slug;
        $_REQUEST[$taxonomy] = $slug;

        if ($paged > 1) {
            $query_vars['paged'] = $paged;
            $_GET['paged'] = (string) $paged;
            $_REQUEST['paged'] = (string) $paged;
            $_SERVER['QUERY_STRING'] = sprintf('%s=%s&paged=%d', $taxonomy, $slug, $paged);
        } else {
            $_SERVER['QUERY_STRING'] = sprintf('%s=%s', $taxonomy, $slug);
        }

        require dirname(__FILE__) . '/wp-load.php';

        add_filter('redirect_canonical', '__return_false');

        wp($query_vars);
        require_once ABSPATH . WPINC . '/template-loader.php';
        exit;
    }
}
