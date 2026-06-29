<?php
if (! defined('ABSPATH')) {
    exit;
}

final class Church_Core_Page_Sections
{
    public const LAYOUT_META_KEY = 'church_section_layout';
    public const PROFILES_META_KEY = 'church_section_profiles';

    private const SCHEMA_VERSION = '2';
    private const SCHEMA_VERSION_OPTION = 'church_core_section_schema_version';
    private const LEGACY_CONTENT_BACKUP_META_KEY = '_church_section_legacy_content_backup';
    private const ATTACHMENT_SOURCE_META_KEY = '_church_section_source_path';
    private const PROFILE_CONTENT_ROWS = 6;
    private const LAYOUT_DEFAULT = 'default';
    private const LAYOUT_FEATURE = 'feature';
    private const LAYOUT_ELDER_BOARD = 'elder_board';

    private static bool $upgrade_attempted = false;

    public static function boot(): void
    {
        add_action('after_setup_theme', [__CLASS__, 'enable_page_thumbnails']);
        add_action('add_meta_boxes', [__CLASS__, 'register_meta_boxes'], 10, 2);
        add_action('save_post_page', [__CLASS__, 'save_meta']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_action('init', [__CLASS__, 'maybe_run_schema_upgrade']);
    }

    public static function enable_page_thumbnails(): void
    {
        add_post_type_support('page', 'thumbnail');
    }

    public static function register_meta_boxes(string $post_type, WP_Post $post): void
    {
        if ($post_type !== 'page' || ! self::is_supported_section($post)) {
            return;
        }

        add_meta_box(
            'church-core-page-section-presentation',
            __('Section Presentation', 'church-core'),
            [__CLASS__, 'render_meta_box'],
            'page',
            'normal',
            'high'
        );
    }

    public static function render_meta_box(WP_Post $post): void
    {
        wp_nonce_field('church_core_page_section_meta', 'church_core_page_section_meta_nonce');

        $layout = self::get_layout($post->ID);
        $profiles = self::get_profiles($post->ID);
        $featured_image_id = get_post_thumbnail_id($post->ID);
        ?>
        <div class="church-core-section-meta" data-section-meta>
            <p>
                <label for="church-core-section-layout"><strong><?php esc_html_e('Section Layout', 'church-core'); ?></strong></label>
            </p>
            <p>
                <select id="church-core-section-layout" name="church_section_layout" data-section-layout-select>
                    <option value="<?php echo esc_attr(self::LAYOUT_DEFAULT); ?>" <?php selected($layout, self::LAYOUT_DEFAULT); ?>>
                        <?php esc_html_e('Default content card', 'church-core'); ?>
                    </option>
                    <option value="<?php echo esc_attr(self::LAYOUT_FEATURE); ?>" <?php selected($layout, self::LAYOUT_FEATURE); ?>>
                        <?php esc_html_e('Feature image with content', 'church-core'); ?>
                    </option>
                    <option value="<?php echo esc_attr(self::LAYOUT_ELDER_BOARD); ?>" <?php selected($layout, self::LAYOUT_ELDER_BOARD); ?>>
                        <?php esc_html_e('Elder board cards', 'church-core'); ?>
                    </option>
                </select>
            </p>

            <div data-section-layout-panel="<?php echo esc_attr(self::LAYOUT_FEATURE); ?>"<?php echo $layout === self::LAYOUT_FEATURE ? '' : ' hidden'; ?>>
                <p><?php esc_html_e('Use the Featured Image panel to choose the section image. The page editor content remains the body copy shown beside it.', 'church-core'); ?></p>
                <?php if ($featured_image_id > 0) : ?>
                    <div>
                        <?php echo wp_kses_post(wp_get_attachment_image($featured_image_id, 'medium')); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div data-section-layout-panel="<?php echo esc_attr(self::LAYOUT_ELDER_BOARD); ?>"<?php echo $layout === self::LAYOUT_ELDER_BOARD ? '' : ' hidden'; ?>>
                <p><?php esc_html_e('The page editor content is used as the section introduction. Each profile card below controls the heading, family label, summary copy, and image.', 'church-core'); ?></p>

                <div data-profile-list>
                    <?php foreach ($profiles as $index => $profile) : ?>
                        <?php self::render_profile_row($profile, $index); ?>
                    <?php endforeach; ?>
                </div>

                <p>
                    <button type="button" class="button" data-profile-add><?php esc_html_e('Add Profile', 'church-core'); ?></button>
                </p>

                <template data-profile-template>
                    <?php self::render_profile_row([
                        'name' => '',
                        'family' => '',
                        'content' => '',
                        'image_id' => 0,
                    ], '__INDEX__'); ?>
                </template>
            </div>
        </div>
        <?php
    }

    public static function save_meta(int $post_id): void
    {
        if (wp_is_post_revision($post_id)) {
            return;
        }

        if (! isset($_POST['church_core_page_section_meta_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['church_core_page_section_meta_nonce'])), 'church_core_page_section_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $post = get_post($post_id);

        if (! $post instanceof WP_Post || $post->post_type !== 'page' || ! self::is_supported_section($post)) {
            return;
        }

        $layout = self::sanitize_layout(isset($_POST['church_section_layout']) ? wp_unslash((string) $_POST['church_section_layout']) : self::LAYOUT_DEFAULT);
        $profiles = self::sanitize_profiles($_POST['church_section_profiles'] ?? []);

        update_post_meta($post_id, self::LAYOUT_META_KEY, $layout);

        if ($profiles === []) {
            delete_post_meta($post_id, self::PROFILES_META_KEY);
            return;
        }

        update_post_meta($post_id, self::PROFILES_META_KEY, $profiles);
    }

    public static function enqueue_admin_assets(string $hook): void
    {
        if (! in_array($hook, ['post-new.php', 'post.php'], true)) {
            return;
        }

        $screen = get_current_screen();

        if (! $screen || $screen->post_type !== 'page') {
            return;
        }

        $post_id = isset($_GET['post']) ? absint(wp_unslash((string) $_GET['post'])) : 0;

        if ($post_id > 0) {
            $post = get_post($post_id);

            if (! $post instanceof WP_Post || ! self::is_supported_section($post)) {
                return;
            }
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'church-core-admin',
            CHURCH_CORE_URL . 'assets/admin.js',
            [],
            filemtime(CHURCH_CORE_PATH . 'assets/admin.js'),
            true
        );
    }

    public static function get_layout(int $post_id): string
    {
        return self::sanitize_layout((string) get_post_meta($post_id, self::LAYOUT_META_KEY, true));
    }

    public static function get_profiles(int $post_id): array
    {
        return self::sanitize_profiles(get_post_meta($post_id, self::PROFILES_META_KEY, true));
    }

    public static function maybe_run_schema_upgrade(): void
    {
        if (self::$upgrade_attempted || wp_installing()) {
            return;
        }

        self::$upgrade_attempted = true;

        if ((string) get_option(self::SCHEMA_VERSION_OPTION, '') === self::SCHEMA_VERSION) {
            return;
        }

        self::run_schema_upgrade_now();
    }

    public static function run_schema_upgrade_now(): void
    {
        if (wp_installing()) {
            return;
        }

        $elder_board_page = self::get_target_page(['about-us/elder-board', 'elder-board']);
        $womens_ministry_page = self::get_target_page(['worship/womens-ministry', 'womens-ministry']);

        if (! ($elder_board_page instanceof WP_Post) && ! ($womens_ministry_page instanceof WP_Post)) {
            return;
        }

        $womens_ministry_ready = true;

        if ($womens_ministry_page instanceof WP_Post) {
            $womens_ministry_ready = self::migrate_womens_ministry_section($womens_ministry_page);
        }

        if ($elder_board_page instanceof WP_Post) {
            self::migrate_elder_board_section($elder_board_page);
        }

        if ($womens_ministry_ready) {
            update_option(self::SCHEMA_VERSION_OPTION, self::SCHEMA_VERSION, false);
        }
    }

    private static function render_profile_row(array $profile, int|string $index): void
    {
        $name = (string) ($profile['name'] ?? '');
        $family = (string) ($profile['family'] ?? '');
        $content = (string) ($profile['content'] ?? '');
        $image_id = absint($profile['image_id'] ?? 0);
        $image_url = $image_id > 0 ? wp_get_attachment_image_url($image_id, 'medium') : '';
        ?>
        <fieldset class="church-core-section-profile" data-profile-row>
            <legend>
                <?php esc_html_e('Profile', 'church-core'); ?>
                <span data-profile-number></span>
            </legend>

            <p>
                <label>
                    <strong><?php esc_html_e('Name', 'church-core'); ?></strong><br>
                    <input class="widefat" type="text" name="church_section_profiles[<?php echo esc_attr((string) $index); ?>][name]" value="<?php echo esc_attr($name); ?>" data-profile-name>
                </label>
            </p>

            <p>
                <label>
                    <strong><?php esc_html_e('Family Label', 'church-core'); ?></strong><br>
                    <input class="widefat" type="text" name="church_section_profiles[<?php echo esc_attr((string) $index); ?>][family]" value="<?php echo esc_attr($family); ?>" data-profile-family>
                </label>
            </p>

            <p>
                <label>
                    <strong><?php esc_html_e('Summary', 'church-core'); ?></strong><br>
                    <textarea class="widefat" rows="<?php echo esc_attr((string) self::PROFILE_CONTENT_ROWS); ?>" name="church_section_profiles[<?php echo esc_attr((string) $index); ?>][content]" data-profile-content><?php echo esc_textarea($content); ?></textarea>
                </label>
            </p>

            <p>
                <input type="hidden" name="church_section_profiles[<?php echo esc_attr((string) $index); ?>][image_id]" value="<?php echo esc_attr((string) $image_id); ?>" data-profile-image-input>
                <button
                    type="button"
                    class="button"
                    data-media-target="[data-profile-image-input]"
                    data-media-preview="[data-profile-image-preview]"
                    data-media-scope="row"
                    data-media-type="image"
                    data-media-title="<?php esc_attr_e('Choose a profile image', 'church-core'); ?>"
                    data-media-button="<?php esc_attr_e('Use this image', 'church-core'); ?>"
                >
                    <?php esc_html_e('Choose Image', 'church-core'); ?>
                </button>
                <button type="button" class="button-link-delete" data-profile-image-clear><?php esc_html_e('Clear image', 'church-core'); ?></button>
            </p>

            <p>
                <img
                    src="<?php echo esc_url($image_url); ?>"
                    alt=""
                    width="240"
                    data-profile-image-preview
                    <?php echo $image_url !== '' ? '' : 'hidden'; ?>
                >
            </p>

            <p>
                <button type="button" class="button" data-profile-move="up"><?php esc_html_e('Move Up', 'church-core'); ?></button>
                <button type="button" class="button" data-profile-move="down"><?php esc_html_e('Move Down', 'church-core'); ?></button>
                <button type="button" class="button-link-delete" data-profile-remove><?php esc_html_e('Remove Profile', 'church-core'); ?></button>
            </p>
        </fieldset>
        <?php
    }

    private static function is_supported_section(WP_Post $post): bool
    {
        if ($post->post_type !== 'page' || (int) $post->post_parent < 1) {
            return false;
        }

        foreach (self::get_supported_parent_ids() as $parent_id) {
            if ((int) $post->post_parent === $parent_id) {
                return true;
            }
        }

        return false;
    }

    private static function get_supported_parent_ids(): array
    {
        static $parent_ids = null;

        if (is_array($parent_ids)) {
            return $parent_ids;
        }

        $parent_ids = [];

        foreach (['about-us', 'worship'] as $slug) {
            $page = get_page_by_path($slug);

            if ($page instanceof WP_Post) {
                $parent_ids[] = (int) $page->ID;
            }
        }

        return $parent_ids;
    }

    private static function get_target_page(array $paths): ?WP_Post
    {
        foreach ($paths as $path) {
            $page = get_page_by_path($path);

            if ($page instanceof WP_Post) {
                return $page;
            }
        }

        return null;
    }

    private static function sanitize_layout(string $value): string
    {
        $value = sanitize_key($value);
        $layouts = [
            self::LAYOUT_DEFAULT,
            self::LAYOUT_FEATURE,
            self::LAYOUT_ELDER_BOARD,
        ];

        return in_array($value, $layouts, true) ? $value : self::LAYOUT_DEFAULT;
    }

    private static function sanitize_profiles(mixed $profiles): array
    {
        if (! is_array($profiles)) {
            return [];
        }

        $sanitized = [];

        foreach ($profiles as $profile) {
            if (! is_array($profile)) {
                continue;
            }

            $name = sanitize_text_field(wp_unslash((string) ($profile['name'] ?? '')));
            $family = sanitize_text_field(wp_unslash((string) ($profile['family'] ?? '')));
            $content = trim(wp_kses_post(wp_unslash((string) ($profile['content'] ?? ''))));
            $image_id = absint($profile['image_id'] ?? 0);

            if ($name === '' && $family === '' && $content === '' && $image_id === 0) {
                continue;
            }

            if ($name === '') {
                continue;
            }

            $sanitized[] = [
                'name' => $name,
                'family' => $family,
                'content' => $content,
                'image_id' => $image_id,
            ];
        }

        return $sanitized;
    }

    private static function migrate_womens_ministry_section(WP_Post $page): bool
    {
        if (! metadata_exists('post', $page->ID, self::LAYOUT_META_KEY)) {
            update_post_meta($page->ID, self::LAYOUT_META_KEY, self::LAYOUT_FEATURE);
        }

        if (self::is_womens_ministry_section_ready($page)) {
            return true;
        }

        $image = self::get_legacy_feature_image_definition();
        $attachment_id = absint(get_post_thumbnail_id($page->ID));

        if ($attachment_id < 1) {
            $attachment_id = self::get_attachment_id_by_source((string) $image['path']);
        }

        if ($attachment_id < 1) {
            $attachment_id = self::import_legacy_image(
                (string) $image['path'],
                (string) $image['alt'],
                __('Women\'s Ministry', 'church-core'),
                $page->ID
            );
        }

        if ($attachment_id > 0 && absint(get_post_thumbnail_id($page->ID)) !== $attachment_id) {
            set_post_thumbnail($page->ID, $attachment_id);
        }

        clean_post_cache($page->ID);

        $page = get_post($page->ID);

        return $page instanceof WP_Post && self::is_womens_ministry_section_ready($page);
    }

    private static function migrate_elder_board_section(WP_Post $page): void
    {
        $parsed = self::parse_legacy_elder_board_markup((string) $page->post_content);
        $profiles = self::get_profiles($page->ID);
        $has_layout = metadata_exists('post', $page->ID, self::LAYOUT_META_KEY);
        $has_profiles_meta = metadata_exists('post', $page->ID, self::PROFILES_META_KEY);

        if (! $has_profiles_meta && $parsed['profiles'] !== []) {
            $profiles = self::build_migrated_profiles($parsed['profiles'], $page->ID);

            if ($profiles !== []) {
                update_post_meta($page->ID, self::PROFILES_META_KEY, $profiles);
            }
        }

        if ($profiles !== []) {
            $repaired_profiles = self::repair_profile_assets($profiles, $page->ID);

            if ($repaired_profiles !== $profiles) {
                $profiles = $repaired_profiles;
                update_post_meta($page->ID, self::PROFILES_META_KEY, $profiles);
            }
        }

        if (! $has_layout && $profiles !== []) {
            update_post_meta($page->ID, self::LAYOUT_META_KEY, self::LAYOUT_ELDER_BOARD);
        }

        if ($parsed['profiles'] === []) {
            return;
        }

        if (! metadata_exists('post', $page->ID, self::LEGACY_CONTENT_BACKUP_META_KEY)) {
            update_post_meta($page->ID, self::LEGACY_CONTENT_BACKUP_META_KEY, (string) $page->post_content);
        }

        $intro = trim((string) $parsed['intro']);

        if ($intro === trim((string) $page->post_content)) {
            return;
        }

        wp_update_post([
            'ID' => $page->ID,
            'post_content' => wp_slash($intro),
        ]);
    }

    private static function build_migrated_profiles(array $profiles, int $page_id): array
    {
        $migrated = [];

        foreach ($profiles as $index => $profile) {
            if (! is_array($profile)) {
                continue;
            }

            $name = sanitize_text_field((string) ($profile['name'] ?? ''));
            $content = trim(wp_kses_post((string) ($profile['content'] ?? '')));

            if ($name === '' || $content === '') {
                continue;
            }

            $asset = self::get_legacy_elder_profile_definition($name, $index);
            $image_id = 0;
            $family = '';

            if (is_array($asset)) {
                $image_id = self::import_legacy_image(
                    (string) $asset['path'],
                    (string) $asset['alt'],
                    $name,
                    $page_id,
                    (string) ($asset['family'] ?? '')
                );
                $family = (string) ($asset['family'] ?? '');
            }

            $migrated[] = [
                'name' => $name,
                'family' => $family,
                'content' => $content,
                'image_id' => $image_id,
            ];
        }

        return $migrated;
    }

    private static function repair_profile_assets(array $profiles, int $page_id): array
    {
        $changed = false;

        foreach ($profiles as $index => $profile) {
            if (! is_array($profile)) {
                continue;
            }

            $asset = self::get_legacy_elder_profile_definition((string) ($profile['name'] ?? ''), $index);

            if (! is_array($asset)) {
                continue;
            }

            if ((string) ($profile['family'] ?? '') === '' && ($asset['family'] ?? '') !== '') {
                $profile['family'] = (string) $asset['family'];
                $changed = true;
            }

            if (absint($profile['image_id'] ?? 0) < 1) {
                $image_id = self::import_legacy_image(
                    (string) $asset['path'],
                    (string) $asset['alt'],
                    (string) ($profile['name'] ?? ''),
                    $page_id,
                    (string) ($asset['family'] ?? '')
                );

                if ($image_id > 0) {
                    $profile['image_id'] = $image_id;
                    $changed = true;
                }
            }

            $profiles[$index] = $profile;
        }

        if (! $changed) {
            return $profiles;
        }

        return self::sanitize_profiles($profiles);
    }

    private static function import_legacy_image(string $relative_path, string $alt, string $title, int $parent_id, string $caption = ''): int
    {
        $relative_path = '/' . ltrim($relative_path, '/');
        $existing_id = self::get_attachment_id_by_source($relative_path);

        if ($existing_id > 0) {
            return $existing_id;
        }

        $source_path = get_theme_file_path($relative_path);

        if (! is_string($source_path) || ! file_exists($source_path)) {
            return 0;
        }

        $uploads = wp_upload_dir();

        if (! empty($uploads['error'])) {
            return 0;
        }

        $contents = file_get_contents($source_path);

        if ($contents === false) {
            return 0;
        }

        $upload = wp_upload_bits(basename($source_path), null, $contents);

        if (! empty($upload['error'])) {
            return 0;
        }

        $destination = (string) ($upload['file'] ?? '');

        if ($destination === '') {
            return 0;
        }

        $filename = basename($destination);
        $filetype = wp_check_filetype($filename, null);
        $attachment_id = wp_insert_attachment([
            'post_mime_type' => (string) ($filetype['type'] ?? 'image/webp'),
            'post_title' => $title !== '' ? $title : pathinfo($filename, PATHINFO_FILENAME),
            'post_excerpt' => $caption,
            'post_status' => 'inherit',
        ], $destination, $parent_id);

        if (is_wp_error($attachment_id) || $attachment_id < 1) {
            @unlink($destination);
            return 0;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';

        $metadata = wp_generate_attachment_metadata($attachment_id, $destination);

        if (is_array($metadata)) {
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        if ($alt !== '') {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
        }

        update_post_meta($attachment_id, self::ATTACHMENT_SOURCE_META_KEY, $relative_path);

        return $attachment_id;
    }

    private static function get_attachment_id_by_source(string $relative_path): int
    {
        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => self::ATTACHMENT_SOURCE_META_KEY,
            'meta_value' => $relative_path,
        ]);

        if (! is_array($attachments) || $attachments === []) {
            return 0;
        }

        return absint($attachments[0]);
    }

    private static function is_womens_ministry_section_ready(WP_Post $page): bool
    {
        if (self::get_layout($page->ID) !== self::LAYOUT_FEATURE) {
            return false;
        }

        if (absint(get_post_thumbnail_id($page->ID)) < 1) {
            return false;
        }

        if (function_exists('church_theme_get_page_section_featured_image')) {
            return is_array(church_theme_get_page_section_featured_image($page));
        }

        return false;
    }

    private static function parse_legacy_elder_board_markup(string $content): array
    {
        $content = trim($content);

        if ($content === '' || ! class_exists('DOMDocument')) {
            return [
                'intro' => $content,
                'profiles' => [],
            ];
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $errors_state = libxml_use_internal_errors(true);

        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="church-core-section-root">' . $content . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($errors_state);

        if (! $loaded) {
            return [
                'intro' => $content,
                'profiles' => [],
            ];
        }

        $wrapper = $document->getElementById('church-core-section-root');

        if (! $wrapper instanceof DOMElement) {
            return [
                'intro' => $content,
                'profiles' => [],
            ];
        }

        $intro_parts = [];
        $profiles = [];
        $current_profile = null;

        foreach (iterator_to_array($wrapper->childNodes) as $node) {
            if (! $node instanceof DOMNode) {
                continue;
            }

            if (strtolower($node->nodeName) === 'h3') {
                if (is_array($current_profile)) {
                    $profiles[] = [
                        'name' => trim((string) ($current_profile['name'] ?? '')),
                        'content' => trim(implode('', $current_profile['content_parts'] ?? [])),
                    ];
                }

                $current_profile = [
                    'name' => wp_strip_all_tags($node->textContent),
                    'content_parts' => [],
                ];
                continue;
            }

            $html = trim((string) $document->saveHTML($node));

            if ($html === '') {
                continue;
            }

            if (is_array($current_profile)) {
                $current_profile['content_parts'][] = $html;
                continue;
            }

            $intro_parts[] = $html;
        }

        if (is_array($current_profile)) {
            $profiles[] = [
                'name' => trim((string) ($current_profile['name'] ?? '')),
                'content' => trim(implode('', $current_profile['content_parts'] ?? [])),
            ];
        }

        $profiles = array_values(array_filter($profiles, static function (array $profile): bool {
            return ($profile['name'] ?? '') !== '' && ($profile['content'] ?? '') !== '';
        }));

        return [
            'intro' => trim(implode('', $intro_parts)),
            'profiles' => $profiles,
        ];
    }

    private static function get_legacy_feature_image_definition(): array
    {
        return [
            'path' => '/assets/images/crossroads/women-ministry.webp',
            'alt' => __('Women of Crossroad South Church gathered for ministry', 'church-core'),
        ];
    }

    private static function get_legacy_elder_profile_definition(string $name, int $index): ?array
    {
        $definitions = [
            [
                'match' => 'benjamin',
                'path' => '/assets/images/crossroads/benji-rashmi.webp',
                'alt' => __('Benjamin and Rashmi of Crossroad South Church', 'church-core'),
                'family' => __('Benjamin & Rashmi', 'church-core'),
            ],
            [
                'match' => 'timothy',
                'path' => '/assets/images/crossroads/tim-ruth.webp',
                'alt' => __('Timothy and Ruth of Crossroad South Church', 'church-core'),
                'family' => __('Timothy & Ruth', 'church-core'),
            ],
            [
                'match' => 'kishore',
                'path' => '/assets/images/crossroads/kishore-shirley.webp',
                'alt' => __('Kishore and Shirley of Crossroad South Church', 'church-core'),
                'family' => __('Kishore & Shirley', 'church-core'),
            ],
        ];

        $normalized_name = strtolower($name);

        foreach ($definitions as $definition) {
            if (str_contains($normalized_name, (string) $definition['match'])) {
                return $definition;
            }
        }

        return $definitions[$index] ?? null;
    }
}
