<?php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Tiny per-user "flash" store backed by a transient: set a one-shot result/notice
 * before an admin redirect, then take it (read + delete) once on the next screen.
 * Shared by the sermon import and YouTube-sync admin screens so the identical
 * persist/consume pattern lives in one place. Each caller passes its own prefix.
 */
final class Church_Core_Admin_Flash
{
    public static function set(string $prefix, array $data, int $ttl = 300): void
    {
        set_transient($prefix . get_current_user_id(), $data, $ttl);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function take(string $prefix): ?array
    {
        $key = $prefix . get_current_user_id();
        $data = get_transient($key);

        if (! is_array($data)) {
            return null;
        }

        delete_transient($key);

        return $data;
    }
}
