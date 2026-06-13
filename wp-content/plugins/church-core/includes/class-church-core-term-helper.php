<?php
if (! defined('ABSPATH')) {
    exit;
}

final class Church_Core_Term_Helper
{
    public static function ensure_term(string $name, string $taxonomy): int|WP_Error
    {
        $existing = term_exists($name, $taxonomy);

        if (is_array($existing) && isset($existing['term_id'])) {
            return (int) $existing['term_id'];
        }

        if (is_string($existing) || is_int($existing)) {
            return (int) $existing;
        }

        $inserted = wp_insert_term($name, $taxonomy);

        if (is_wp_error($inserted)) {
            return $inserted;
        }

        return (int) $inserted['term_id'];
    }
}
