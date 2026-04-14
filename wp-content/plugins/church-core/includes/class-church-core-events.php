<?php
if (! defined('ABSPATH')) {
    exit;
}

final class Church_Core_Events
{
    public static function boot(): void
    {
        add_action('init', [__CLASS__, 'register_content']);
        add_action('add_meta_boxes', [__CLASS__, 'register_meta_boxes']);
        add_action('save_post_event', [__CLASS__, 'save_meta']);
        add_filter('manage_event_posts_columns', [__CLASS__, 'event_columns']);
        add_action('manage_event_posts_custom_column', [__CLASS__, 'render_event_column'], 10, 2);
    }

    public static function register_content(): void
    {
        register_post_type('event', [
            'labels' => [
                'name' => __('Events', 'church-core'),
                'singular_name' => __('Event', 'church-core'),
                'add_new_item' => __('Add New Event', 'church-core'),
                'edit_item' => __('Edit Event', 'church-core'),
            ],
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => ['title', 'editor'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'events'],
            'menu_position' => 22,
        ]);
    }

    public static function register_meta_boxes(): void
    {
        add_meta_box(
            'church-core-event-details',
            __('Event Details', 'church-core'),
            [__CLASS__, 'render_meta_box'],
            'event',
            'normal',
            'high'
        );
    }

    public static function render_meta_box(WP_Post $post): void
    {
        wp_nonce_field('church_core_event_meta', 'church_core_event_meta_nonce');

        $event_start = (string) get_post_meta($post->ID, 'event_start', true);
        $event_location = (string) get_post_meta($post->ID, 'event_location', true);
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="church-core-event-start"><?php esc_html_e('Event Date & Time', 'church-core'); ?></label></th>
                    <td>
                        <input class="regular-text" type="datetime-local" id="church-core-event-start" name="event_start" value="<?php echo esc_attr(self::format_input_datetime($event_start)); ?>" step="60" required>
                        <p class="description"><?php esc_html_e('Use the church site’s local time when entering the start date and time.', 'church-core'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="church-core-event-location"><?php esc_html_e('Location', 'church-core'); ?></label></th>
                    <td>
                        <input class="regular-text" type="text" id="church-core-event-location" name="event_location" value="<?php echo esc_attr($event_location); ?>" placeholder="<?php esc_attr_e('Mother Theresa Hall or Online', 'church-core'); ?>" required>
                        <p class="description"><?php esc_html_e('Add the place, room, or a simple label like Online. Put meeting links or bring-list notes in the editor below.', 'church-core'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public static function save_meta(int $post_id): void
    {
        if (! isset($_POST['church_core_event_meta_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['church_core_event_meta_nonce'])), 'church_core_event_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $event_start = isset($_POST['event_start']) ? self::normalize_event_start(wp_unslash((string) $_POST['event_start'])) : '';
        $event_location = isset($_POST['event_location']) ? sanitize_text_field(wp_unslash((string) $_POST['event_location'])) : '';

        if ($event_start === '') {
            delete_post_meta($post_id, 'event_start');
        } else {
            update_post_meta($post_id, 'event_start', $event_start);
        }

        if ($event_location === '') {
            delete_post_meta($post_id, 'event_location');
        } else {
            update_post_meta($post_id, 'event_location', $event_location);
        }
    }

    public static function event_columns(array $columns): array
    {
        $columns['event_start'] = __('Event Date & Time', 'church-core');
        $columns['event_location'] = __('Location', 'church-core');

        return $columns;
    }

    public static function render_event_column(string $column, int $post_id): void
    {
        if ($column === 'event_start') {
            $event_start = (string) get_post_meta($post_id, 'event_start', true);
            echo esc_html($event_start !== '' ? self::format_display_datetime($event_start) : '—');
        }

        if ($column === 'event_location') {
            echo esc_html((string) get_post_meta($post_id, 'event_location', true) ?: '—');
        }
    }

    private static function format_input_datetime(string $value): string
    {
        $date = self::parse_event_datetime($value);

        return $date ? $date->format('Y-m-d\TH:i') : '';
    }

    private static function format_display_datetime(string $value): string
    {
        $date = self::parse_event_datetime($value);

        if (! $date) {
            return $value;
        }

        return wp_date(get_option('date_format') . ' ' . get_option('time_format'), $date->getTimestamp(), wp_timezone());
    }

    private static function normalize_event_start(string $value): string
    {
        $date = self::parse_event_datetime($value);

        return $date ? $date->format('Y-m-d H:i:s') : '';
    }

    private static function parse_event_datetime(string $value): ?DateTimeImmutable
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $timezone = wp_timezone();
        $formats = [
            'Y-m-d\TH:i',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value, $timezone);

            if (! $date instanceof DateTimeImmutable) {
                continue;
            }

            $errors = DateTimeImmutable::getLastErrors();

            if (! is_array($errors) || (($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0)) {
                return $date;
            }
        }

        $timestamp = strtotime($value);

        if (! $timestamp) {
            return null;
        }

        return (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone);
    }
}
