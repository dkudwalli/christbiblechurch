<?php
if (! defined('ABSPATH')) {
    exit;
}

final class Church_Core
{
    public static function boot(): void
    {
        Church_Core_Sermons::boot();
        Church_Core_Sermon_Import::boot();
        Church_Core_Events::boot();
        Church_Core_Contact::boot();
    }

    public static function activate(): void
    {
        self::boot();
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}
