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
        Church_Core_Sermon_Cron::boot();
        Church_Core_Sermon_Sync_Admin::boot();
        Church_Core_Events::boot();
        Church_Core_Contact::boot();
    }

    public static function activate(): void
    {
        self::boot();
        Church_Core_Sermon_Cron::refresh_schedule();
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        Church_Core_Sermon_Cron::clear_scheduled_event();
        flush_rewrite_rules();
    }
}
