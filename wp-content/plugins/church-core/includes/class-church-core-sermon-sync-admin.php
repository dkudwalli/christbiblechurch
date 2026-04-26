<?php
if (! defined('ABSPATH')) {
    exit;
}

final class Church_Core_Sermon_Sync_Admin
{
    private const PAGE_SLUG = 'church-core-youtube-sync';
    private const SETTINGS_GROUP = 'church_core_sermon_sync';
    private const NOTICE_TRANSIENT_PREFIX = 'church_core_sermon_sync_notice_';
    private const NOTICE_TTL = 300;

    private static string $page_hook = '';

    public static function boot(): void
    {
        add_action('admin_menu', [__CLASS__, 'register_admin_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_action('admin_post_church_core_sermon_sync_now', [__CLASS__, 'handle_manual_sync']);
    }

    public static function register_admin_page(): void
    {
        self::$page_hook = (string) add_submenu_page(
            'edit.php?post_type=sermon',
            __('YouTube Sync', 'church-core'),
            __('YouTube Sync', 'church-core'),
            'manage_categories',
            self::PAGE_SLUG,
            [__CLASS__, 'render_page']
        );
    }

    public static function register_settings(): void
    {
        register_setting(
            self::SETTINGS_GROUP,
            Church_Core_Sermon_Cron::SETTINGS_OPTION,
            [
                'type' => 'array',
                'sanitize_callback' => [__CLASS__, 'sanitize_settings'],
                'default' => Church_Core_Sermon_Cron::get_default_settings(),
            ]
        );
    }

    public static function sanitize_settings($settings): array
    {
        $defaults = Church_Core_Sermon_Cron::get_default_settings();
        $settings = is_array($settings) ? $settings : [];
        $settings = wp_parse_args($settings, $defaults);
        $schedule_time = $defaults['schedule_time'];

        if (preg_match('/^(2[0-3]|[01]?\d):([0-5]\d)$/', (string) $settings['schedule_time'], $matches) === 1) {
            $schedule_time = sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        $valid_weekdays = array_keys(self::get_weekday_options());
        $schedule_weekday = sanitize_key((string) $settings['schedule_weekday']);

        if (! in_array($schedule_weekday, $valid_weekdays, true)) {
            $schedule_weekday = $defaults['schedule_weekday'];
        }

        $sanitized = [
            'api_key' => sanitize_text_field((string) $settings['api_key']),
            'channel_id' => sanitize_text_field((string) $settings['channel_id']),
            'schedule_weekday' => $schedule_weekday,
            'schedule_time' => $schedule_time,
        ];

        self::sanitize_default_speaker_setting($settings, $sanitized);

        return $sanitized;
    }

    public static function enqueue_admin_assets(string $hook): void
    {
        if ($hook !== self::$page_hook) {
            return;
        }

        wp_enqueue_script(
            'church-core-admin',
            CHURCH_CORE_URL . 'assets/admin.js',
            [],
            filemtime(CHURCH_CORE_PATH . 'assets/admin.js'),
            true
        );
    }

    private static function sanitize_default_speaker_setting(array $settings, array &$sanitized): void
    {
        $speaker_choice = isset($settings['default_speaker_term_id'])
            ? sanitize_text_field((string) $settings['default_speaker_term_id'])
            : '';

        if ($speaker_choice === 'other') {
            $speaker_name = isset($settings['default_speaker_name'])
                ? sanitize_text_field((string) $settings['default_speaker_name'])
                : '';

            if ($speaker_name === '') {
                self::add_settings_error(__('Enter a default speaker name, or choose an existing speaker.', 'church-core'));
                self::preserve_previous_default_speaker_setting($sanitized);

                return;
            }

            $speaker_term_id = self::resolve_or_create_speaker_term_id($speaker_name);

            if (is_wp_error($speaker_term_id)) {
                self::add_settings_error($speaker_term_id->get_error_message());
                self::preserve_previous_default_speaker_setting($sanitized);

                return;
            }

            $sanitized['default_speaker_term_id'] = $speaker_term_id;

            return;
        }

        $speaker_term_id = absint($speaker_choice);

        if ($speaker_term_id <= 0) {
            self::add_settings_error(__('Choose a default speaker, or use Other to create one.', 'church-core'));
            self::preserve_previous_default_speaker_setting($sanitized);

            return;
        }

        if (! self::is_valid_speaker_term_id($speaker_term_id)) {
            self::add_settings_error(__('The selected default speaker no longer exists. Choose another speaker.', 'church-core'));
            self::preserve_previous_default_speaker_setting($sanitized);

            return;
        }

        $sanitized['default_speaker_term_id'] = $speaker_term_id;
    }

    private static function resolve_or_create_speaker_term_id(string $speaker_name)
    {
        $existing_term = term_exists($speaker_name, 'speaker');

        if (is_array($existing_term) && isset($existing_term['term_id'])) {
            return (int) $existing_term['term_id'];
        }

        if (is_string($existing_term) || is_int($existing_term)) {
            return (int) $existing_term;
        }

        $inserted_term = wp_insert_term($speaker_name, 'speaker');

        if (is_wp_error($inserted_term)) {
            return new WP_Error(
                'church_core_sermon_sync_speaker_insert_failed',
                sprintf(
                    __('Could not create the default speaker term "%1$s": %2$s', 'church-core'),
                    $speaker_name,
                    $inserted_term->get_error_message()
                )
            );
        }

        return (int) $inserted_term['term_id'];
    }

    private static function preserve_previous_default_speaker_setting(array &$sanitized): void
    {
        if (! Church_Core_Sermon_Cron::has_default_speaker_setting()) {
            return;
        }

        $sanitized['default_speaker_term_id'] = Church_Core_Sermon_Cron::get_default_speaker_term_id();
    }

    private static function is_valid_speaker_term_id(int $speaker_term_id): bool
    {
        if ($speaker_term_id <= 0) {
            return false;
        }

        $term = get_term($speaker_term_id, 'speaker');

        return $term instanceof WP_Term && ! is_wp_error($term);
    }

    private static function get_speaker_terms(): array
    {
        $terms = get_terms([
            'taxonomy' => 'speaker',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        return is_wp_error($terms) ? [] : $terms;
    }

    private static function add_settings_error(string $message): void
    {
        add_settings_error(
            self::SETTINGS_GROUP,
            'church_core_sermon_sync_default_speaker',
            $message,
            'error'
        );
    }

    public static function render_page(): void
    {
        self::assert_permissions();

        $settings = Church_Core_Sermon_Cron::get_settings();
        $speaker_terms = self::get_speaker_terms();
        $has_saved_default_speaker = Church_Core_Sermon_Cron::has_default_speaker_setting();
        $selected_default_speaker_term_id = Church_Core_Sermon_Cron::get_default_speaker_term_id();
        $selected_default_speaker_exists = $selected_default_speaker_term_id > 0 && self::is_valid_speaker_term_id($selected_default_speaker_term_id);
        $last_run = Church_Core_Sermon_Cron::get_last_run();
        $logs = Church_Core_Sermon_Cron::get_log_entries();
        $next_run = Church_Core_Sermon_Cron::get_next_scheduled_timestamp();
        $timezone_label = wp_timezone_string();

        if ($timezone_label === '') {
            $timezone_label = __('UTC (set Settings > General for local church time)', 'church-core');
        }

        $notice = self::consume_notice();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('YouTube Sync', 'church-core'); ?></h1>

            <?php settings_errors(self::SETTINGS_GROUP); ?>

            <?php if (is_array($notice)) : ?>
                <?php
                $notice_status = (string) ($notice['status'] ?? '');
                $notice_class = $notice_status === 'error'
                    ? 'notice notice-error'
                    : ($notice_status === 'partial' ? 'notice notice-warning' : 'notice notice-success');
                ?>
                <div class="<?php echo esc_attr($notice_class); ?>">
                    <p><?php echo esc_html((string) ($notice['message'] ?? '')); ?></p>
                </div>
            <?php endif; ?>

            <p><?php esc_html_e('Sync newly uploaded YouTube sermons into the sermon archive. Automatic sync runs on the schedule below once the church channel upload is available, and you can always trigger a manual sync if needed.', 'church-core'); ?></p>

            <form method="post" action="options.php">
                <?php settings_fields(self::SETTINGS_GROUP); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="church-core-youtube-api-key"><?php esc_html_e('YouTube API Key', 'church-core'); ?></label>
                            </th>
                            <td>
                                <input class="regular-text" type="password" id="church-core-youtube-api-key" name="<?php echo esc_attr(Church_Core_Sermon_Cron::SETTINGS_OPTION); ?>[api_key]" value="<?php echo esc_attr((string) $settings['api_key']); ?>" autocomplete="off">
                                <p class="description"><?php esc_html_e('Use a YouTube Data API v3 key with access to channels, playlistItems, and videos.', 'church-core'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="church-core-youtube-channel-id"><?php esc_html_e('Channel ID', 'church-core'); ?></label>
                            </th>
                            <td>
                                <input class="regular-text" type="text" id="church-core-youtube-channel-id" name="<?php echo esc_attr(Church_Core_Sermon_Cron::SETTINGS_OPTION); ?>[channel_id]" value="<?php echo esc_attr((string) $settings['channel_id']); ?>" placeholder="UCxxxxxxxxxxxxxxxxxxxxxx">
                                <p class="description"><?php esc_html_e('Paste the channel ID for the church YouTube channel, not the @handle.', 'church-core'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="church-core-default-speaker"><?php esc_html_e('Default Speaker', 'church-core'); ?></label>
                            </th>
                            <td>
                                <select class="regular-text" id="church-core-default-speaker" name="<?php echo esc_attr(Church_Core_Sermon_Cron::SETTINGS_OPTION); ?>[default_speaker_term_id]" data-default-speaker-choice>
                                    <option value=""><?php esc_html_e('Select a speaker', 'church-core'); ?></option>
                                    <?php if ($has_saved_default_speaker && $selected_default_speaker_term_id > 0 && ! $selected_default_speaker_exists) : ?>
                                        <option value="<?php echo esc_attr((string) $selected_default_speaker_term_id); ?>" selected>
                                            <?php
                                            printf(
                                                esc_html__('Missing speaker (term ID %d)', 'church-core'),
                                                $selected_default_speaker_term_id
                                            );
                                            ?>
                                        </option>
                                    <?php endif; ?>
                                    <?php foreach ($speaker_terms as $speaker_term) : ?>
                                        <option value="<?php echo esc_attr((string) $speaker_term->term_id); ?>" <?php selected($selected_default_speaker_term_id, (int) $speaker_term->term_id); ?>>
                                            <?php echo esc_html($speaker_term->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="other"><?php esc_html_e('Other', 'church-core'); ?></option>
                                </select>
                                <div class="church-core-default-speaker-custom" data-default-speaker-custom hidden style="margin-top: 8px;">
                                    <label for="church-core-default-speaker-name" class="screen-reader-text"><?php esc_html_e('New default speaker name', 'church-core'); ?></label>
                                    <input class="regular-text" type="text" id="church-core-default-speaker-name" name="<?php echo esc_attr(Church_Core_Sermon_Cron::SETTINGS_OPTION); ?>[default_speaker_name]" value="" placeholder="<?php esc_attr_e('Speaker name', 'church-core'); ?>" disabled>
                                </div>
                                <?php if (! $has_saved_default_speaker) : ?>
                                    <p class="description"><?php esc_html_e('Until a default speaker is saved, YouTube sync will use the fallback speaker "Unknown".', 'church-core'); ?></p>
                                <?php elseif ($selected_default_speaker_term_id <= 0) : ?>
                                    <p class="description"><?php esc_html_e('No default speaker is saved. Choose one before the next sync.', 'church-core'); ?></p>
                                <?php elseif ($selected_default_speaker_term_id > 0 && ! $selected_default_speaker_exists) : ?>
                                    <p class="description"><?php esc_html_e('The saved default speaker could not be found. Choose another speaker before the next sync.', 'church-core'); ?></p>
                                <?php else : ?>
                                    <p class="description"><?php esc_html_e('New sermons imported from YouTube will be assigned to this speaker.', 'church-core'); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Weekly Sync Schedule', 'church-core'); ?></th>
                            <td>
                                <select name="<?php echo esc_attr(Church_Core_Sermon_Cron::SETTINGS_OPTION); ?>[schedule_weekday]">
                                    <?php foreach (self::get_weekday_options() as $value => $label) : ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected((string) $settings['schedule_weekday'], $value); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="time" name="<?php echo esc_attr(Church_Core_Sermon_Cron::SETTINGS_OPTION); ?>[schedule_time]" value="<?php echo esc_attr((string) $settings['schedule_time']); ?>" step="60">
                                <p class="description">
                                    <?php
                                    printf(
                                        esc_html__('The schedule uses the WordPress timezone from Settings > General. Current timezone: %s.', 'church-core'),
                                        $timezone_label
                                    );
                                    ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(__('Save YouTube Sync Settings', 'church-core')); ?>
            </form>

            <hr>

            <h2><?php esc_html_e('Manual Sync', 'church-core'); ?></h2>
            <p><?php esc_html_e('Run a sync immediately if a sermon upload arrives outside the weekly schedule or if the scheduled sync failed.', 'church-core'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="church_core_sermon_sync_now">
                <?php wp_nonce_field('church_core_sermon_sync_now'); ?>
                <?php submit_button(__('Run Sync Now', 'church-core'), 'secondary', 'submit', false); ?>
            </form>

            <hr>

            <h2><?php esc_html_e('Sync Status', 'church-core'); ?></h2>
            <table class="widefat striped" style="max-width: 960px;">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Next scheduled sync', 'church-core'); ?></th>
                        <td>
                            <?php
                            echo $next_run > 0
                                ? esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $next_run, wp_timezone()))
                                : esc_html__('Automatic sync is disabled until the API key and channel ID are saved.', 'church-core');
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Last sync', 'church-core'); ?></th>
                        <td>
                            <?php
                            echo ! empty($last_run['timestamp'])
                                ? esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $last_run['timestamp'], wp_timezone()))
                                : esc_html__('No sync has run yet.', 'church-core');
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Last status', 'church-core'); ?></th>
                        <td><?php echo esc_html((string) ($last_run['status'] ?: '—')); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Last trigger', 'church-core'); ?></th>
                        <td><?php echo esc_html((string) ($last_run['trigger'] ?: '—')); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Last result', 'church-core'); ?></th>
                        <td><?php echo esc_html((string) ($last_run['message'] ?: '—')); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Last counts', 'church-core'); ?></th>
                        <td>
                            <?php
                            printf(
                                esc_html__('Created: %1$d, Backfilled: %2$d, Skipped: %3$d', 'church-core'),
                                (int) ($last_run['created'] ?? 0),
                                (int) ($last_run['backfilled'] ?? 0),
                                (int) ($last_run['skipped'] ?? 0)
                            );
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php if (($last_run['errors'] ?? []) !== []) : ?>
                <h3><?php esc_html_e('Last Sync Errors', 'church-core'); ?></h3>
                <ul>
                    <?php foreach ((array) $last_run['errors'] as $error_message) : ?>
                        <li><?php echo esc_html((string) $error_message); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <h2><?php esc_html_e('Recent Log', 'church-core'); ?></h2>
            <?php if ($logs === []) : ?>
                <p><?php esc_html_e('No sync log entries yet.', 'church-core'); ?></p>
            <?php else : ?>
                <table class="widefat striped" style="max-width: 960px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('When', 'church-core'); ?></th>
                            <th><?php esc_html_e('Level', 'church-core'); ?></th>
                            <th><?php esc_html_e('Message', 'church-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $entry) : ?>
                            <tr>
                                <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) ($entry['timestamp'] ?? 0), wp_timezone())); ?></td>
                                <td><?php echo esc_html((string) ($entry['level'] ?? 'info')); ?></td>
                                <td><?php echo esc_html((string) ($entry['message'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function handle_manual_sync(): void
    {
        self::assert_permissions();
        check_admin_referer('church_core_sermon_sync_now');

        $result = Church_Core_Sermon_Cron::run_sync('manual');
        self::persist_notice($result);

        wp_safe_redirect(self::get_page_url());
        exit;
    }

    private static function get_page_url(): string
    {
        return admin_url('edit.php?post_type=sermon&page=' . self::PAGE_SLUG);
    }

    private static function get_weekday_options(): array
    {
        return [
            'sunday' => __('Sunday', 'church-core'),
            'monday' => __('Monday', 'church-core'),
            'tuesday' => __('Tuesday', 'church-core'),
            'wednesday' => __('Wednesday', 'church-core'),
            'thursday' => __('Thursday', 'church-core'),
            'friday' => __('Friday', 'church-core'),
            'saturday' => __('Saturday', 'church-core'),
        ];
    }

    private static function assert_permissions(): void
    {
        $post_type_object = get_post_type_object('sermon');
        $publish_cap = $post_type_object instanceof WP_Post_Type ? $post_type_object->cap->publish_posts : 'publish_posts';

        if (! current_user_can('manage_categories') || ! current_user_can($publish_cap)) {
            wp_die(
                esc_html__('You do not have permission to manage sermon sync.', 'church-core'),
                esc_html__('Forbidden', 'church-core'),
                ['response' => 403]
            );
        }
    }

    private static function persist_notice(array $result): void
    {
        set_transient(
            self::NOTICE_TRANSIENT_PREFIX . get_current_user_id(),
            $result,
            self::NOTICE_TTL
        );
    }

    private static function consume_notice(): ?array
    {
        $key = self::NOTICE_TRANSIENT_PREFIX . get_current_user_id();
        $notice = get_transient($key);

        if (! is_array($notice)) {
            return null;
        }

        delete_transient($key);

        return $notice;
    }
}
