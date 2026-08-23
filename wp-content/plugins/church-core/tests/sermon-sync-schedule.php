<?php
/**
 * wp-cli: wp eval-file wp-content/plugins/church-core/tests/sermon-sync-schedule.php
 *
 * Pins the weekly sync schedule: always a future Sunday 12:30 in the site
 * timezone, on WP's own 'weekly' recurrence, and installs still carrying the
 * old single-event schedule get converted rather than left to expire.
 */

if (! defined('ABSPATH')) {
    exit;
}

$hook = Church_Core_Sermon_Cron::EVENT_HOOK;
$option = Church_Core_Sermon_Cron::SETTINGS_OPTION;
$previous_timezone = get_option('timezone_string');
$previous_settings = get_option($option);

update_option('timezone_string', 'Asia/Kolkata');
update_option($option, ['api_key' => 'test-key', 'channel_id' => 'test-channel'], false);

$assert = static function (bool $condition, string $message): void {
    if (! $condition) {
        echo $message, PHP_EOL;
        exit(1);
    }
};

// Fresh install: arms a weekly event on a future Sunday at 12:30 local.
wp_clear_scheduled_hook($hook);
Church_Core_Sermon_Cron::ensure_schedule();
$event = wp_get_scheduled_event($hook);

$assert($event !== false, 'Expected ensure_schedule() to arm the sync event.');
$assert($event->schedule === 'weekly', 'Expected the WP "weekly" recurrence, got: ' . var_export($event->schedule, true));
$assert($event->timestamp > time(), 'Expected the next run to be in the future.');
$assert(wp_date('w', $event->timestamp) === '0', 'Expected a Sunday, got: ' . wp_date('D', $event->timestamp));
$assert(wp_date('H:i', $event->timestamp) === '12:30', 'Expected 12:30 local, got: ' . wp_date('H:i', $event->timestamp));

// Already armed: leaves the existing event alone.
$first_timestamp = $event->timestamp;
Church_Core_Sermon_Cron::ensure_schedule();
$assert(wp_get_scheduled_event($hook)->timestamp === $first_timestamp, 'ensure_schedule() should be idempotent.');

// Legacy single-event schedule: converted to the weekly recurrence.
wp_clear_scheduled_hook($hook);
wp_schedule_single_event(time() + DAY_IN_SECONDS, $hook);
Church_Core_Sermon_Cron::ensure_schedule();
$assert(wp_get_scheduled_event($hook)->schedule === 'weekly', 'A pre-existing single event should be converted to weekly.');

// Unconfigured: nothing scheduled.
delete_option($option);
Church_Core_Sermon_Cron::ensure_schedule();
$assert(wp_next_scheduled($hook) === false, 'An unconfigured sync should not stay scheduled.');

wp_clear_scheduled_hook($hook);

if ($previous_settings === false) {
    delete_option($option);
} else {
    update_option($option, $previous_settings, false);
}

update_option('timezone_string', $previous_timezone === false ? '' : $previous_timezone);

echo 'Sermon sync schedule checks passed.', PHP_EOL;
