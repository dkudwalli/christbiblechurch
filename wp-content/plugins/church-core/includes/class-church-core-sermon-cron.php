<?php
if (! defined('ABSPATH')) {
    exit;
}

final class Church_Core_Sermon_Cron
{
    public const EVENT_HOOK = 'church_core_sermon_sync_run';
    public const SETTINGS_OPTION = 'church_core_sermon_sync_settings';
    public const LAST_RUN_OPTION = 'church_core_sermon_sync_last_run';
    public const LOG_OPTION = 'church_core_sermon_sync_log';

    private const LOCK_TRANSIENT = 'church_core_sermon_sync_lock';
    private const LOCK_TTL = 15 * MINUTE_IN_SECONDS;
    private const LOG_LIMIT = 20;

    public static function boot(): void
    {
        add_action(self::EVENT_HOOK, [__CLASS__, 'handle_scheduled_sync']);
        add_action('init', [__CLASS__, 'ensure_schedule']);
        add_action('add_option_' . self::SETTINGS_OPTION, [__CLASS__, 'handle_settings_changed'], 10, 2);
        add_action('update_option_' . self::SETTINGS_OPTION, [__CLASS__, 'handle_settings_changed'], 10, 2);
    }

    public static function get_default_settings(): array
    {
        return [
            'api_key' => '',
            'channel_id' => '',
            'schedule_weekday' => 'sunday',
            'schedule_time' => '12:30',
        ];
    }

    public static function get_settings(): array
    {
        $settings = get_option(self::SETTINGS_OPTION, []);

        if (! is_array($settings)) {
            $settings = [];
        }

        return wp_parse_args($settings, self::get_default_settings());
    }

    public static function get_last_run(): array
    {
        $last_run = get_option(self::LAST_RUN_OPTION, []);

        if (! is_array($last_run)) {
            $last_run = [];
        }

        return wp_parse_args($last_run, [
            'timestamp' => 0,
            'trigger' => '',
            'status' => '',
            'message' => '',
            'created' => 0,
            'backfilled' => 0,
            'skipped' => 0,
            'errors' => [],
        ]);
    }

    public static function get_log_entries(): array
    {
        $logs = get_option(self::LOG_OPTION, []);

        return is_array($logs) ? $logs : [];
    }

    public static function get_next_scheduled_timestamp(): int
    {
        $timestamp = wp_next_scheduled(self::EVENT_HOOK);

        return is_int($timestamp) ? $timestamp : 0;
    }

    public static function handle_scheduled_sync(): void
    {
        self::run_sync('cron');
        self::schedule_next_event();
    }

    public static function run_sync(string $trigger = 'manual'): array
    {
        if (get_transient(self::LOCK_TRANSIENT)) {
            $result = [
                'timestamp' => time(),
                'trigger' => $trigger,
                'status' => 'error',
                'message' => __('A YouTube sync is already running.', 'church-core'),
                'created' => 0,
                'backfilled' => 0,
                'skipped' => 0,
                'errors' => [__('A YouTube sync is already running.', 'church-core')],
            ];

            self::store_last_run($result);
            self::append_log('warning', __('Skipped a duplicate YouTube sync attempt because another sync was already running.', 'church-core'));

            return $result;
        }

        set_transient(self::LOCK_TRANSIENT, 1, self::LOCK_TTL);

        try {
            $settings = self::get_settings();

            if (! self::has_valid_configuration($settings)) {
                $message = __('YouTube sync is not configured. Add the API key and channel ID before running a sync.', 'church-core');
                $result = [
                    'timestamp' => time(),
                    'trigger' => $trigger,
                    'status' => 'error',
                    'message' => $message,
                    'created' => 0,
                    'backfilled' => 0,
                    'skipped' => 0,
                    'errors' => [$message],
                ];

                self::store_last_run($result);
                self::append_log('error', $message);

                return $result;
            }

            $service = new Church_Core_Sermon_Sync_Service(
                new Church_Core_Youtube_Client(
                    (string) $settings['api_key'],
                    (string) $settings['channel_id']
                )
            );

            $service_result = $service->sync_recent_sermons();
            $result = [
                'timestamp' => time(),
                'trigger' => $trigger,
                'status' => (string) ($service_result['status'] ?? 'success'),
                'message' => (string) ($service_result['message'] ?? ''),
                'created' => (int) ($service_result['created'] ?? 0),
                'backfilled' => (int) ($service_result['backfilled'] ?? 0),
                'skipped' => (int) ($service_result['skipped'] ?? 0),
                'errors' => isset($service_result['errors']) && is_array($service_result['errors']) ? $service_result['errors'] : [],
            ];

            self::store_last_run($result);
            self::append_log(
                $result['status'] === 'error' ? 'error' : ($result['status'] === 'partial' ? 'warning' : 'info'),
                $result['message'],
                [
                    'trigger' => $trigger,
                    'created' => $result['created'],
                    'backfilled' => $result['backfilled'],
                    'skipped' => $result['skipped'],
                ]
            );

            return $result;
        } finally {
            delete_transient(self::LOCK_TRANSIENT);
        }
    }

    public static function ensure_schedule(): void
    {
        if (! self::has_valid_configuration(self::get_settings())) {
            self::clear_scheduled_event();

            return;
        }

        if (self::get_next_scheduled_timestamp() > 0) {
            return;
        }

        self::schedule_next_event();
    }

    public static function refresh_schedule(): void
    {
        self::clear_scheduled_event();

        if (self::has_valid_configuration(self::get_settings())) {
            self::schedule_next_event();
        }
    }

    public static function clear_scheduled_event(): void
    {
        $timestamp = wp_next_scheduled(self::EVENT_HOOK);

        while (is_int($timestamp) && $timestamp > 0) {
            wp_unschedule_event($timestamp, self::EVENT_HOOK);
            $timestamp = wp_next_scheduled(self::EVENT_HOOK);
        }
    }

    public static function handle_settings_changed(): void
    {
        self::refresh_schedule();
    }

    private static function schedule_next_event(): void
    {
        $timestamp = self::calculate_next_run_timestamp();

        if ($timestamp <= 0) {
            return;
        }

        wp_schedule_single_event($timestamp, self::EVENT_HOOK);
    }

    private static function calculate_next_run_timestamp(): int
    {
        $settings = self::get_settings();
        $weekday = self::sanitize_weekday((string) $settings['schedule_weekday']);
        $time = self::sanitize_time((string) $settings['schedule_time']);
        [$hour, $minute] = array_map('intval', explode(':', $time));

        $timezone = wp_timezone();
        $now = new DateTimeImmutable('now', $timezone);
        $target_weekday = (int) date('w', strtotime($weekday));
        $current_weekday = (int) $now->format('w');
        $days_ahead = ($target_weekday - $current_weekday + 7) % 7;
        $candidate = $now->setTime($hour, $minute, 0);

        if ($days_ahead > 0) {
            $candidate = $candidate->modify('+' . $days_ahead . ' days');
        }

        if ($candidate <= $now) {
            $candidate = $candidate->modify('+7 days');
        }

        return $candidate->getTimestamp();
    }

    private static function sanitize_weekday(string $weekday): string
    {
        $weekday = strtolower(trim($weekday));
        $valid_weekdays = [
            'sunday',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
        ];

        return in_array($weekday, $valid_weekdays, true) ? $weekday : 'sunday';
    }

    private static function sanitize_time(string $time): string
    {
        if (preg_match('/^(2[0-3]|[01]?\d):([0-5]\d)$/', $time, $matches) !== 1) {
            return '12:30';
        }

        return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
    }

    private static function has_valid_configuration(array $settings): bool
    {
        return trim((string) ($settings['api_key'] ?? '')) !== '' && trim((string) ($settings['channel_id'] ?? '')) !== '';
    }

    private static function store_last_run(array $result): void
    {
        update_option(self::LAST_RUN_OPTION, $result, false);
    }

    private static function append_log(string $level, string $message, array $context = []): void
    {
        $message = trim($message);

        if ($message === '') {
            return;
        }

        $logs = self::get_log_entries();
        array_unshift($logs, [
            'timestamp' => time(),
            'level' => sanitize_key($level),
            'message' => $message,
            'context' => $context,
        ]);

        update_option(self::LOG_OPTION, array_slice($logs, 0, self::LOG_LIMIT), false);
    }
}
