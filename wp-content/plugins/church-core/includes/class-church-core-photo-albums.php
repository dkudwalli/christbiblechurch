<?php
if (! defined('ABSPATH')) {
    exit;
}

final class Church_Core_Photo_Albums
{
    use Church_Core_Route_Shim_Writer;

    public const DATE_META_KEY = 'album_date';
    public const PHOTO_IDS_META_KEY = 'album_photo_ids';
    private const ROUTE_ROOT = 'photo-albums';
    private const ROUTE_SHIM_VERSION = '1';
    private const ROUTE_SHIM_VERSION_OPTION = 'church_core_photo_album_route_shim_version';
    private const ROUTE_SHIM_NOTICE_OPTION = 'church_core_photo_album_route_shim_notice';
    private const REWRITE_VERSION = '1';
    private const REWRITE_VERSION_OPTION = 'church_core_photo_album_rewrite_version';
    private const REWRITE_ATTEMPTS_OPTION = 'church_core_photo_album_rewrite_attempts';
    private const REWRITE_MAX_ATTEMPTS = 3;
    private const SYNCED_ROUTE_SLUG_META_KEY = '_church_core_photo_album_route_shim_slug';

    public static function boot(): void
    {
        add_action('init', [__CLASS__, 'register_content']);
        add_action('init', [__CLASS__, 'maybe_reconcile_route_shims'], 20);
        add_action('init', [__CLASS__, 'maybe_run_rewrite_upgrade'], 20);
        add_action('add_meta_boxes', [__CLASS__, 'register_meta_boxes']);
        add_action('save_post_photo_album', [__CLASS__, 'save_meta']);
        add_action('save_post_photo_album', [__CLASS__, 'sync_route_shim_on_save'], 20, 3);
        add_action('trashed_post', [__CLASS__, 'handle_trashed_post']);
        add_action('untrashed_post', [__CLASS__, 'handle_untrashed_post']);
        add_action('before_delete_post', [__CLASS__, 'handle_before_delete_post']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_action('admin_notices', [__CLASS__, 'maybe_render_route_shim_notice']);
        add_filter('manage_photo_album_posts_columns', [__CLASS__, 'album_columns']);
        add_action('manage_photo_album_posts_custom_column', [__CLASS__, 'render_album_column'], 10, 2);
    }

    public static function register_content(): void
    {
        register_post_type('photo_album', [
            'labels' => [
                'name' => __('Photo Albums', 'church-core'),
                'singular_name' => __('Photo Album', 'church-core'),
                'add_new_item' => __('Add New Photo Album', 'church-core'),
                'edit_item' => __('Edit Photo Album', 'church-core'),
            ],
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-format-gallery',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
            'has_archive' => false,
            'rewrite' => ['slug' => 'photo-albums'],
            'menu_position' => 23,
        ]);
    }

    public static function register_meta_boxes(): void
    {
        add_meta_box(
            'church-core-photo-album-details',
            __('Album Details', 'church-core'),
            [__CLASS__, 'render_details_meta_box'],
            'photo_album',
            'normal',
            'high'
        );

        add_meta_box(
            'church-core-photo-album-photos',
            __('Album Photos', 'church-core'),
            [__CLASS__, 'render_photos_meta_box'],
            'photo_album',
            'normal',
            'high'
        );
    }

    public static function render_details_meta_box(WP_Post $post): void
    {
        wp_nonce_field('church_core_photo_album_meta', 'church_core_photo_album_meta_nonce');

        $album_date = self::get_album_date($post->ID);
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="church-core-album-date"><?php esc_html_e('Album Date', 'church-core'); ?></label></th>
                    <td>
                        <input class="regular-text" type="date" id="church-core-album-date" name="album_date" value="<?php echo esc_attr($album_date); ?>">
                        <p class="description"><?php esc_html_e('Used for the main gallery ordering and the album card date label.', 'church-core'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public static function render_photos_meta_box(WP_Post $post): void
    {
        $photo_ids = self::get_photo_ids($post->ID);
        ?>
        <div class="church-core-album-photos" data-album-photos-root>
            <p><?php esc_html_e('Choose the photos for this album from the media library, then drag to reorder them. Attachment captions will appear in the lightbox on the public album page.', 'church-core'); ?></p>

            <p>
                <button
                    type="button"
                    class="button"
                    data-album-photo-picker
                    data-media-title="<?php esc_attr_e('Choose album photos', 'church-core'); ?>"
                    data-media-button="<?php esc_attr_e('Use these photos', 'church-core'); ?>"
                >
                    <?php esc_html_e('Choose Album Photos', 'church-core'); ?>
                </button>
            </p>

            <ul class="church-core-album-photo-list" data-album-photo-list>
                <?php foreach ($photo_ids as $photo_id) : ?>
                    <?php self::render_photo_item($photo_id); ?>
                <?php endforeach; ?>
            </ul>

            <template data-album-photo-template>
                <?php self::render_photo_item(0); ?>
            </template>
        </div>
        <?php
    }

    public static function save_meta(int $post_id): void
    {
        if (! isset($_POST['church_core_photo_album_meta_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['church_core_photo_album_meta_nonce'])), 'church_core_photo_album_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $album_date = isset($_POST['album_date']) ? self::sanitize_album_date(wp_unslash((string) $_POST['album_date'])) : '';
        $photo_ids = self::sanitize_photo_ids($_POST['album_photo_ids'] ?? []);

        if ($album_date === '') {
            delete_post_meta($post_id, self::DATE_META_KEY);
        } else {
            update_post_meta($post_id, self::DATE_META_KEY, $album_date);
        }

        if ($photo_ids === []) {
            delete_post_meta($post_id, self::PHOTO_IDS_META_KEY);
        } else {
            update_post_meta($post_id, self::PHOTO_IDS_META_KEY, $photo_ids);
        }
    }

    public static function sync_route_shim_on_save(int $post_id, WP_Post $post): void
    {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if ($post->post_type !== 'photo_album') {
            return;
        }

        self::sync_route_shim_for_post($post);
    }

    public static function handle_trashed_post(int $post_id): void
    {
        $post = get_post($post_id);

        if (! $post instanceof WP_Post || $post->post_type !== 'photo_album') {
            return;
        }

        self::sync_route_shim_for_post($post);
    }

    public static function handle_untrashed_post(int $post_id): void
    {
        $post = get_post($post_id);

        if (! $post instanceof WP_Post || $post->post_type !== 'photo_album') {
            return;
        }

        self::sync_route_shim_for_post($post);
    }

    public static function handle_before_delete_post(int $post_id): void
    {
        $post = get_post($post_id);

        if (! $post instanceof WP_Post || $post->post_type !== 'photo_album') {
            return;
        }

        self::remove_route_shims_for_post($post->ID, $post->post_name);
    }

    public static function maybe_reconcile_route_shims(): void
    {
        if (wp_installing()) {
            return;
        }

        if (get_option(self::ROUTE_SHIM_VERSION_OPTION) === self::ROUTE_SHIM_VERSION) {
            return;
        }

        $post_ids = get_posts([
            'fields' => 'ids',
            'numberposts' => -1,
            'post_status' => 'publish',
            'post_type' => 'photo_album',
            'suppress_filters' => true,
        ]);
        $all_synced = true;

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);

            if (! $post instanceof WP_Post) {
                continue;
            }

            $all_synced = self::sync_route_shim_for_post($post) && $all_synced;
        }

        if (! $all_synced) {
            return;
        }

        update_option(self::ROUTE_SHIM_VERSION_OPTION, self::ROUTE_SHIM_VERSION, false);
        self::clear_route_shim_notice();
    }

    public static function maybe_run_rewrite_upgrade(): void
    {
        if (wp_installing()) {
            return;
        }

        if (get_option(self::REWRITE_VERSION_OPTION) === self::REWRITE_VERSION) {
            return;
        }

        // Bound the self-heal: without this, a state where the photo-album rules
        // never appear (so the version is never recorded) would re-run
        // flush_rewrite_rules() on every init forever. Cap the attempts per version
        // epoch; once exhausted, stop flushing and rely on the documented manual
        // "Save Permalinks" fallback.
        $attempts_raw = get_option(self::REWRITE_ATTEMPTS_OPTION);
        $attempts = is_array($attempts_raw) && ($attempts_raw['version'] ?? '') === self::REWRITE_VERSION
            ? (int) ($attempts_raw['count'] ?? 0)
            : 0;

        if ($attempts >= self::REWRITE_MAX_ATTEMPTS) {
            return;
        }

        update_option(
            self::REWRITE_ATTEMPTS_OPTION,
            ['version' => self::REWRITE_VERSION, 'count' => $attempts + 1],
            false
        );

        if (! post_type_exists('photo_album')) {
            self::register_content();
        }

        flush_rewrite_rules(false);

        if (! self::stored_rewrite_rules_include_photo_albums()) {
            return;
        }

        update_option(self::REWRITE_VERSION_OPTION, self::REWRITE_VERSION, false);
        delete_option(self::REWRITE_ATTEMPTS_OPTION);
    }

    public static function maybe_render_route_shim_notice(): void
    {
        if (! function_exists('get_current_screen')) {
            return;
        }

        $screen = get_current_screen();

        if (! $screen || $screen->post_type !== 'photo_album') {
            return;
        }

        $notices = get_option(self::ROUTE_SHIM_NOTICE_OPTION);

        if (! is_array($notices) || $notices === []) {
            return;
        }

        delete_option(self::ROUTE_SHIM_NOTICE_OPTION);

        // Notices are a map keyed by slug; tolerate the older single-notice shape.
        if (isset($notices['reason']) || isset($notices['path'])) {
            $notices = [$notices];
        }
        ?>
        <div class="notice notice-error">
            <?php foreach ($notices as $notice) : ?>
                <?php if (is_array($notice)) : ?>
                    <p>
                        <?php
                        echo esc_html(
                            sprintf(
                                'Photo album route fallback could not be synchronized for slug "%1$s". Hostinger may continue showing "No content found." Path: %2$s. %3$s',
                                isset($notice['slug']) && $notice['slug'] !== '' ? (string) $notice['slug'] : '(unknown)',
                                isset($notice['path']) && $notice['path'] !== '' ? (string) $notice['path'] : '(unknown)',
                                isset($notice['reason']) && $notice['reason'] !== '' ? (string) $notice['reason'] : 'Check that WordPress can write to the site root.'
                            )
                        );
                        ?>
                    </p>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    public static function enqueue_admin_assets(string $hook): void
    {
        if (! in_array($hook, ['post-new.php', 'post.php'], true)) {
            return;
        }

        $screen = get_current_screen();

        if (! $screen || $screen->post_type !== 'photo_album') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style(
            'church-core-admin',
            CHURCH_CORE_URL . 'assets/admin.css',
            [],
            filemtime(CHURCH_CORE_PATH . 'assets/admin.css')
        );
        wp_enqueue_script(
            'church-core-admin',
            CHURCH_CORE_URL . 'assets/admin.js',
            [],
            filemtime(CHURCH_CORE_PATH . 'assets/admin.js'),
            true
        );
    }

    public static function album_columns(array $columns): array
    {
        $columns['album_date'] = __('Album Date', 'church-core');
        $columns['album_photo_count'] = __('Photos', 'church-core');

        return $columns;
    }

    public static function render_album_column(string $column, int $post_id): void
    {
        if ($column === 'album_date') {
            $album_date = self::get_album_date($post_id);

            if ($album_date === '') {
                echo '—';
                return;
            }

            $timestamp = strtotime($album_date);
            echo esc_html($timestamp ? wp_date(get_option('date_format'), $timestamp) : $album_date);
            return;
        }

        if ($column === 'album_photo_count') {
            echo esc_html((string) count(self::get_photo_ids($post_id)));
        }
    }

    public static function get_album_date(int $post_id): string
    {
        return self::sanitize_album_date((string) get_post_meta($post_id, self::DATE_META_KEY, true));
    }

    public static function get_photo_ids(int $post_id): array
    {
        return self::sanitize_photo_ids(get_post_meta($post_id, self::PHOTO_IDS_META_KEY, true));
    }

    private static function sync_route_shim_for_post(WP_Post $post): bool
    {
        $current_slug = self::normalize_route_slug($post->post_name);

        if ($post->post_status !== 'publish' || $current_slug === '') {
            return self::remove_route_shims_for_post($post->ID, $current_slug);
        }

        $previous_slug = self::get_synced_route_slug($post->ID);
        $all_synced = true;

        if ($previous_slug !== '' && $previous_slug !== $current_slug) {
            $all_synced = self::remove_route_shim($previous_slug) && $all_synced;
        }

        $all_synced = self::ensure_route_shim($current_slug) && $all_synced;

        if (! $all_synced) {
            return false;
        }

        update_post_meta($post->ID, self::SYNCED_ROUTE_SLUG_META_KEY, $current_slug);

        return true;
    }

    private static function remove_route_shims_for_post(int $post_id, string $current_slug = ''): bool
    {
        $slugs = array_values(array_unique(array_filter([
            self::get_synced_route_slug($post_id),
            self::normalize_route_slug($current_slug),
        ])));
        $all_removed = true;

        foreach ($slugs as $slug) {
            $all_removed = self::remove_route_shim($slug) && $all_removed;
        }

        if (! $all_removed) {
            return false;
        }

        delete_post_meta($post_id, self::SYNCED_ROUTE_SLUG_META_KEY);

        return true;
    }

    private static function remove_route_shim(string $slug): bool
    {
        $slug = self::normalize_route_slug($slug);

        if ($slug === '') {
            return true;
        }

        $route_file = self::get_route_file_path($slug);

        if (file_exists($route_file) && ! @unlink($route_file)) {
            self::store_route_shim_notice($slug, $route_file, 'Could not remove the album shim file.');
            return false;
        }

        $route_directory = self::get_route_directory_path($slug);

        if (! is_dir($route_directory)) {
            self::clear_route_shim_notice($slug);
            return true;
        }

        $directory_contents = scandir($route_directory);

        if (! is_array($directory_contents)) {
            self::store_route_shim_notice($slug, $route_directory, 'Could not inspect the album shim directory.');
            return false;
        }

        $remaining_entries = array_diff($directory_contents, ['.', '..']);

        if ($remaining_entries !== []) {
            self::store_route_shim_notice($slug, $route_directory, 'The album shim directory still contains unexpected files.');
            return false;
        }

        if (! @rmdir($route_directory)) {
            self::store_route_shim_notice($slug, $route_directory, 'Could not remove the empty album shim directory.');
            return false;
        }

        self::clear_route_shim_notice($slug);

        return true;
    }

    protected static function get_route_shim_contents(): string
    {
        return "<?php\nrequire dirname(dirname(__DIR__)) . '/index.php';\n";
    }

    private static function stored_rewrite_rules_include_photo_albums(): bool
    {
        $rewrite_rules = get_option('rewrite_rules', []);

        if (! is_array($rewrite_rules)) {
            return false;
        }

        foreach ($rewrite_rules as $rule => $query) {
            if (str_contains((string) $rule, 'photo-albums/') || str_contains((string) $query, 'photo_album=')) {
                return true;
            }
        }

        return false;
    }

    private static function get_synced_route_slug(int $post_id): string
    {
        return self::normalize_route_slug((string) get_post_meta($post_id, self::SYNCED_ROUTE_SLUG_META_KEY, true));
    }

    private static function render_photo_item(int $photo_id): void
    {
        $preview_html = '';
        $title = '';
        $caption = '';

        if ($photo_id > 0) {
            $preview_html = (string) wp_get_attachment_image($photo_id, 'thumbnail', false, [
                'class' => 'church-core-album-photo__image',
                'loading' => 'lazy',
                'decoding' => 'async',
            ]);
            $title = get_the_title($photo_id) ?: '';
            $attachment = get_post($photo_id);
            $caption = $attachment instanceof WP_Post ? trim((string) $attachment->post_excerpt) : '';
        }
        ?>
        <li class="church-core-album-photo" data-album-photo-item draggable="true">
            <input type="hidden" name="album_photo_ids[]" value="<?php echo esc_attr((string) $photo_id); ?>" data-album-photo-input>

            <div class="church-core-album-photo__preview" data-album-photo-preview>
                <?php if ($preview_html !== '') : ?>
                    <?php echo wp_kses_post($preview_html); ?>
                <?php else : ?>
                    <span class="church-core-album-photo__placeholder"><?php esc_html_e('No image selected', 'church-core'); ?></span>
                <?php endif; ?>
            </div>

            <div class="church-core-album-photo__body">
                <strong data-album-photo-title><?php echo esc_html($title !== '' ? $title : __('Selected photo', 'church-core')); ?></strong>
                <p data-album-photo-caption><?php echo esc_html($caption !== '' ? $caption : __('No caption set for this image.', 'church-core')); ?></p>
            </div>

            <div class="church-core-album-photo__actions">
                <button type="button" class="button-link" data-album-photo-drag-handle><?php esc_html_e('Drag', 'church-core'); ?></button>
                <button type="button" class="button-link-delete" data-album-photo-remove><?php esc_html_e('Remove', 'church-core'); ?></button>
            </div>
        </li>
        <?php
    }

    private static function sanitize_album_date(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value, wp_timezone());
        $errors = DateTimeImmutable::getLastErrors();

        if (! $date instanceof DateTimeImmutable || (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0))) {
            return '';
        }

        return $date->format('Y-m-d');
    }

    private static function sanitize_photo_ids(mixed $photo_ids): array
    {
        if (! is_array($photo_ids)) {
            return [];
        }

        $sanitized = [];

        foreach ($photo_ids as $photo_id) {
            $resolved_id = absint($photo_id);

            if ($resolved_id < 1 || get_post_type($resolved_id) !== 'attachment') {
                continue;
            }

            if (in_array($resolved_id, $sanitized, true)) {
                continue;
            }

            $sanitized[] = $resolved_id;
        }

        return $sanitized;
    }
}
