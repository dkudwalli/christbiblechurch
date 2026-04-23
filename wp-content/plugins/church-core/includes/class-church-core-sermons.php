<?php
if (! defined('ABSPATH')) {
    exit;
}

final class Church_Core_Sermons
{
    public static function boot(): void
    {
        add_action('init', [__CLASS__, 'register_content']);
        add_action('add_meta_boxes', [__CLASS__, 'register_meta_boxes']);
        add_action('save_post_sermon', [__CLASS__, 'save_meta']);
        add_action('pre_get_posts', [__CLASS__, 'tune_archive_query']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_filter('manage_sermon_posts_columns', [__CLASS__, 'sermon_columns']);
        add_action('manage_sermon_posts_custom_column', [__CLASS__, 'render_sermon_column'], 10, 2);
    }

    public static function register_content(): void
    {
        register_post_type('sermon', [
            'labels' => [
                'name' => __('Sermons', 'church-core'),
                'singular_name' => __('Sermon', 'church-core'),
                'add_new_item' => __('Add New Sermon', 'church-core'),
                'edit_item' => __('Edit Sermon', 'church-core'),
            ],
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-video-alt3',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'sermons'],
            'menu_position' => 21,
        ]);

        register_taxonomy('series', ['sermon'], [
            'labels' => [
                'name' => __('Series', 'church-core'),
                'singular_name' => __('Series', 'church-core'),
                'search_items' => __('Search Series', 'church-core'),
                'all_items' => __('All Series', 'church-core'),
                'edit_item' => __('Edit Series', 'church-core'),
                'update_item' => __('Update Series', 'church-core'),
                'add_new_item' => __('Add New Series', 'church-core'),
                'new_item_name' => __('New Series Name', 'church-core'),
                'menu_name' => __('Series', 'church-core'),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_rest' => false,
            'hierarchical' => false,
            'meta_box_cb' => false,
            'rewrite' => ['slug' => 'series'],
        ]);

        register_taxonomy('speaker', ['sermon'], [
            'labels' => [
                'name' => __('Speakers', 'church-core'),
                'singular_name' => __('Speaker', 'church-core'),
            ],
            'public' => true,
            'show_in_rest' => true,
            'hierarchical' => false,
            'rewrite' => ['slug' => 'speaker'],
        ]);
    }

    public static function register_meta_boxes(): void
    {
        add_meta_box(
            'church-core-sermon-details',
            __('Sermon Details', 'church-core'),
            [__CLASS__, 'render_meta_box'],
            'sermon',
            'normal',
            'high'
        );
    }

    public static function render_meta_box(WP_Post $post): void
    {
        wp_nonce_field('church_core_sermon_meta', 'church_core_sermon_meta_nonce');

        $series_terms = get_terms([
            'taxonomy' => 'series',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        $current_series = get_the_terms($post->ID, 'series');
        $current_series_id = $current_series && ! is_wp_error($current_series) ? (int) $current_series[0]->term_id : 0;

        if (is_wp_error($series_terms)) {
            $series_terms = [];
        }

        $fields = [
            'sermon_date' => (string) get_post_meta($post->ID, 'sermon_date', true),
            'scripture_reference' => (string) get_post_meta($post->ID, 'scripture_reference', true),
            'youtube_url' => (string) get_post_meta($post->ID, 'youtube_url', true),
            'audio_url' => (string) get_post_meta($post->ID, 'audio_url', true),
        ];
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="church-core-sermon-date"><?php esc_html_e('Sermon Date', 'church-core'); ?></label></th>
                    <td><input class="regular-text" type="date" id="church-core-sermon-date" name="sermon_date" value="<?php echo esc_attr($fields['sermon_date']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="church-core-sermon-series"><?php esc_html_e('Series', 'church-core'); ?></label></th>
                    <td>
                        <select class="regular-text" id="church-core-sermon-series" name="sermon_series">
                            <option value="0"><?php esc_html_e('No series', 'church-core'); ?></option>
                            <?php foreach ($series_terms as $series_term) : ?>
                                <option value="<?php echo esc_attr((string) $series_term->term_id); ?>" <?php selected($current_series_id, (int) $series_term->term_id); ?>>
                                    <?php echo esc_html($series_term->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($series_terms === []) : ?>
                            <p class="description">
                                <?php
                                printf(
                                    wp_kses(
                                        __('No sermon series exist yet. <a href="%s">Create your first series</a>.', 'church-core'),
                                        ['a' => ['href' => []]]
                                    ),
                                    esc_url(admin_url('edit-tags.php?taxonomy=series&post_type=sermon'))
                                );
                                ?>
                            </p>
                        <?php else : ?>
                            <p class="description">
                                <?php
                                printf(
                                    wp_kses(
                                        __('Choose one series for this sermon, or <a href="%s">manage series</a>.', 'church-core'),
                                        ['a' => ['href' => []]]
                                    ),
                                    esc_url(admin_url('edit-tags.php?taxonomy=series&post_type=sermon'))
                                );
                                ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="church-core-scripture-reference"><?php esc_html_e('Scripture Reference', 'church-core'); ?></label></th>
                    <td><input class="regular-text" type="text" id="church-core-scripture-reference" name="scripture_reference" value="<?php echo esc_attr($fields['scripture_reference']); ?>" placeholder="Romans 8:28-39"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="church-core-youtube-url"><?php esc_html_e('YouTube URL', 'church-core'); ?></label></th>
                    <td><input class="regular-text" type="url" id="church-core-youtube-url" name="youtube_url" value="<?php echo esc_attr($fields['youtube_url']); ?>" placeholder="https://www.youtube.com/watch?v="></td>
                </tr>
                <tr>
                    <th scope="row"><label for="church-core-audio-url"><?php esc_html_e('Audio URL', 'church-core'); ?></label></th>
                    <td>
                        <input class="regular-text" type="url" id="church-core-audio-url" name="audio_url" value="<?php echo esc_attr($fields['audio_url']); ?>" placeholder="https://example.com/sermon.mp3">
                        <p>
                            <button type="button" class="button" data-media-target="#church-core-audio-url"><?php esc_html_e('Choose from Media Library', 'church-core'); ?></button>
                        </p>
                        <p class="description"><?php esc_html_e('Use an uploaded MP3 from the media library or paste an external audio URL.', 'church-core'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public static function save_meta(int $post_id): void
    {
        if (! isset($_POST['church_core_sermon_meta_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['church_core_sermon_meta_nonce'])), 'church_core_sermon_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $series_id = isset($_POST['sermon_series']) ? absint(wp_unslash((string) $_POST['sermon_series'])) : 0;
        wp_set_object_terms($post_id, $series_id > 0 ? [$series_id] : [], 'series', false);

        $fields = [
            'sermon_date' => 'sanitize_text_field',
            'scripture_reference' => 'sanitize_text_field',
            'youtube_url' => 'esc_url_raw',
            'audio_url' => 'esc_url_raw',
        ];

        foreach ($fields as $meta_key => $sanitize_callback) {
            $value = isset($_POST[$meta_key]) ? wp_unslash($_POST[$meta_key]) : '';
            $value = $sanitize_callback($value);

            if ($value === '') {
                delete_post_meta($post_id, $meta_key);
                continue;
            }

            update_post_meta($post_id, $meta_key, $value);
        }

        $youtube_url = (string) get_post_meta($post_id, 'youtube_url', true);
        $youtube_video_id = Church_Core_Youtube_Client::extract_video_id_from_url($youtube_url);

        if ($youtube_video_id === '') {
            delete_post_meta($post_id, 'youtube_video_id');
        } else {
            update_post_meta($post_id, 'youtube_video_id', $youtube_video_id);
        }
    }

    public static function tune_archive_query(WP_Query $query): void
    {
        if (is_admin() || ! $query->is_main_query()) {
            return;
        }

        if (! $query->is_post_type_archive('sermon') && ! $query->is_tax(['speaker', 'series'])) {
            return;
        }

        $active_taxonomy_filters = [];

        if ($query->is_tax(['speaker', 'series'])) {
            $taxonomy = (string) $query->get('taxonomy');
            $term_slug = sanitize_title((string) $query->get('term'));

            if (in_array($taxonomy, ['speaker', 'series'], true) && $term_slug !== '') {
                $active_taxonomy_filters[$taxonomy] = $term_slug;
            }
        }

        foreach (['speaker', 'series'] as $taxonomy) {
            if (isset($active_taxonomy_filters[$taxonomy])) {
                continue;
            }

            $value = isset($_GET[$taxonomy]) ? sanitize_title(wp_unslash((string) $_GET[$taxonomy])) : '';

            if ($value !== '') {
                $active_taxonomy_filters[$taxonomy] = $value;
            }
        }

        $query->set('post_type', 'sermon');
        $query->set('posts_per_page', 9);
        $query->set('meta_key', 'sermon_date');
        $query->set('orderby', 'meta_value');
        $query->set('meta_type', 'DATE');
        $query->set('order', 'DESC');

        if ($active_taxonomy_filters !== []) {
            $tax_query = [];

            foreach ($active_taxonomy_filters as $taxonomy => $term_slug) {
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $term_slug,
                ];
            }

            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }

            $query->set('tax_query', $tax_query);
        }
    }

    public static function enqueue_admin_assets(string $hook): void
    {
        if (! in_array($hook, ['post-new.php', 'post.php'], true)) {
            return;
        }

        $screen = get_current_screen();

        if (! $screen || $screen->post_type !== 'sermon') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'church-core-admin',
            CHURCH_CORE_URL . 'assets/admin.js',
            ['jquery'],
            filemtime(CHURCH_CORE_PATH . 'assets/admin.js'),
            true
        );
    }

    public static function sermon_columns(array $columns): array
    {
        $columns['speaker'] = __('Speaker', 'church-core');
        $columns['series'] = __('Series', 'church-core');
        $columns['sermon_date'] = __('Sermon Date', 'church-core');

        return $columns;
    }

    public static function render_sermon_column(string $column, int $post_id): void
    {
        if ($column === 'speaker') {
            $terms = get_the_terms($post_id, 'speaker');
            echo $terms && ! is_wp_error($terms) ? esc_html($terms[0]->name) : '—';
        }

        if ($column === 'series') {
            $terms = get_the_terms($post_id, 'series');
            echo $terms && ! is_wp_error($terms) ? esc_html($terms[0]->name) : '—';
        }

        if ($column === 'sermon_date') {
            $date = (string) get_post_meta($post_id, 'sermon_date', true);
            echo $date !== '' ? esc_html($date) : '—';
        }
    }
}
