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
            'default_speaker_term_id' => 0,
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

    public static function has_default_speaker_setting(): bool
    {
        $settings = get_option(self::SETTINGS_OPTION, []);

        return is_array($settings) && array_key_exists('default_speaker_term_id', $settings);
    }

    public static function get_default_speaker_term_id(): int
    {
        $settings = get_option(self::SETTINGS_OPTION, []);

        if (! is_array($settings) || ! array_key_exists('default_speaker_term_id', $settings)) {
            return 0;
        }

        return absint($settings['default_speaker_term_id']);
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
                self::maybe_alert_cron_failure($trigger, $result);

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
            self::maybe_alert_cron_failure($trigger, $result);

            return $result;
        } finally {
            delete_transient(self::LOCK_TRANSIENT);
        }
    }

    /**
     * Email the site admin when a SCHEDULED (cron) sync fails or partially fails.
     * Manual runs already surface a notice on the YouTube Sync screen, so only the
     * unattended cron trigger needs a push. Lock-contention skips are intentionally
     * not alerted (transient, self-resolving). Recipient is filterable.
     */
    private static function maybe_alert_cron_failure(string $trigger, array $result): void
    {
        if ($trigger !== 'cron') {
            return;
        }

        $status = (string) ($result['status'] ?? '');

        if ($status !== 'error' && $status !== 'partial') {
            return;
        }

        $recipient = apply_filters('church_core_sermon_sync_alert_recipient', get_option('admin_email'));

        if (! is_string($recipient) || $recipient === '') {
            return;
        }

        $errors = isset($result['errors']) && is_array($result['errors']) ? array_map('strval', $result['errors']) : [];
        /* translators: %s: site name. */
        $subject = sprintf(__('[%s] Weekly sermon sync needs attention', 'church-core'), wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES));
        $body = implode("\n", array_filter([
            /* translators: %s: sync status (error or partial). */
            sprintf(__('The scheduled YouTube sermon sync finished with status: %s', 'church-core'), $status),
            (string) ($result['message'] ?? ''),
            $errors !== [] ? "\n" . __('Errors:', 'church-core') . "\n- " . implode("\n- ", $errors) : '',
            "\n" . __('Open Sermons → YouTube Sync in the dashboard for the full log.', 'church-core'),
        ], static fn ($line): bool => $line !== ''));

        wp_mail($recipient, $subject, $body);
    }

    public static function ensure_schedule(): void
    {
        if (! self::has_valid_configuration(self::get_settings())) {
            self::clear_scheduled_event();

            return;
        }

        $event = wp_get_scheduled_event(self::EVENT_HOOK);

        if ($event && $event->schedule === 'weekly') {
            return;
        }

        // Converts installs still carrying the pre-'weekly' single-event schedule.
        self::clear_scheduled_event();
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
        wp_clear_scheduled_hook(self::EVENT_HOOK);
    }

    public static function handle_settings_changed(): void
    {
        self::refresh_schedule();
    }

    /**
     * Sundays at 12:30 in the site timezone, on WP's own 'weekly' recurrence — so
     * nothing has to re-arm the event after each run.
     */
    private static function schedule_next_event(): void
    {
        $next = new DateTimeImmutable('today 12:30', wp_timezone());

        if ($next->format('w') !== '0' || $next->getTimestamp() <= time()) {
            $next = new DateTimeImmutable('next sunday 12:30', wp_timezone());
        }

        wp_schedule_event($next->getTimestamp(), 'weekly', self::EVENT_HOOK);
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
