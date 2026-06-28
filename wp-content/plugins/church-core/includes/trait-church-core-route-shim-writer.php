<?php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Shared "create a file-based route shim" helpers for CPTs that mirror their
 * published pretty URLs into index.php shim directories at the site root (the
 * Hostinger file-based routing fallback documented in CLAUDE.md).
 *
 * A using class MUST define:
 *   - const ROUTE_ROOT                — top-level directory under ABSPATH (e.g. 'sermons')
 *   - const ROUTE_SHIM_NOTICE_OPTION  — option name for the admin failure notice
 *   - protected static get_route_shim_contents(): string — the exact index.php body to write
 *
 * These helpers only ever CREATE shims (ensure_route_shim is create-if-missing via a
 * byte-for-byte content compare, so an existing/committed shim is never rewritten).
 * Any removal/rename logic lives in the using class, not here.
 */
trait Church_Core_Route_Shim_Writer
{
    abstract protected static function get_route_shim_contents(): string;

    protected static function ensure_route_shim(string $slug): bool
    {
        $slug = self::normalize_route_slug($slug);

        if ($slug === '') {
            return false;
        }

        $route_root = self::get_route_root_path();

        if (! is_dir($route_root) && ! wp_mkdir_p($route_root)) {
            self::store_route_shim_notice($slug, $route_root, 'Could not create the route directory.');
            return false;
        }

        $route_directory = self::get_route_directory_path($slug);

        if (! is_dir($route_directory) && ! wp_mkdir_p($route_directory)) {
            self::store_route_shim_notice($slug, $route_directory, 'Could not create the route shim directory.');
            return false;
        }

        $route_file = self::get_route_file_path($slug);
        $expected_contents = self::get_route_shim_contents();
        $existing_contents = file_exists($route_file) ? file_get_contents($route_file) : false;

        if ($existing_contents === $expected_contents) {
            return true;
        }

        $bytes_written = @file_put_contents($route_file, $expected_contents, LOCK_EX);

        if ($bytes_written === false) {
            self::store_route_shim_notice($slug, $route_file, 'Could not write the route shim file.');
            return false;
        }

        return true;
    }

    protected static function get_route_root_path(): string
    {
        return trailingslashit(ABSPATH) . self::ROUTE_ROOT;
    }

    protected static function get_route_directory_path(string $slug): string
    {
        return self::get_route_root_path() . '/' . self::normalize_route_slug($slug);
    }

    protected static function get_route_file_path(string $slug): string
    {
        return self::get_route_directory_path($slug) . '/index.php';
    }

    protected static function normalize_route_slug(string $slug): string
    {
        return sanitize_title($slug);
    }

    protected static function store_route_shim_notice(string $slug, string $path, string $reason): void
    {
        update_option(self::ROUTE_SHIM_NOTICE_OPTION, [
            'path' => $path,
            'reason' => $reason,
            'slug' => self::normalize_route_slug($slug),
        ], false);
    }

    protected static function clear_route_shim_notice(): void
    {
        delete_option(self::ROUTE_SHIM_NOTICE_OPTION);
    }
}
