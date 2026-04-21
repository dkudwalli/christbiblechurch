<?php
if (! defined('ABSPATH')) {
    exit;
}

final class Church_Core_Sermon_Import
{
    private const PAGE_SLUG = 'church-core-sermon-import';
    private const RESULT_TRANSIENT_PREFIX = 'church_core_sermon_import_result_';
    private const RESULT_TTL = 300;

    public static function boot(): void
    {
        add_action('admin_menu', [__CLASS__, 'register_admin_page']);
        add_action('admin_post_church_core_sermon_csv_template', [__CLASS__, 'handle_template_download']);
        add_action('admin_post_church_core_sermon_csv_import', [__CLASS__, 'handle_import']);
    }

    public static function register_admin_page(): void
    {
        add_submenu_page(
            'edit.php?post_type=sermon',
            __('Import Sermons', 'church-core'),
            __('Import Sermons', 'church-core'),
            'manage_categories',
            self::PAGE_SLUG,
            [__CLASS__, 'render_import_page']
        );
    }

    public static function render_import_page(): void
    {
        self::assert_permissions();

        $columns = self::get_csv_columns();
        $result = self::consume_result();
        $template_url = wp_nonce_url(
            admin_url('admin-post.php?action=church_core_sermon_csv_template'),
            'church_core_sermon_csv_template'
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Import Sermons', 'church-core'); ?></h1>

            <?php if (is_array($result)) : ?>
                <?php
                $notice_class = ($result['skipped'] ?? 0) > 0 || ($result['errors'] ?? []) !== []
                    ? 'notice notice-warning'
                    : 'notice notice-success';
                ?>
                <div class="<?php echo esc_attr($notice_class); ?>">
                    <p>
                        <?php
                        printf(
                            esc_html__('Imported %1$d sermons. Skipped %2$d rows.', 'church-core'),
                            (int) ($result['imported'] ?? 0),
                            (int) ($result['skipped'] ?? 0)
                        );
                        ?>
                    </p>
                    <?php if (($result['errors'] ?? []) !== []) : ?>
                        <ul>
                            <?php foreach ($result['errors'] as $message) : ?>
                                <li><?php echo esc_html((string) $message); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <p><?php esc_html_e('Download the sample CSV, fill in one sermon per row, then import it here. Imports are create-only, publish immediately, and auto-create missing speaker and series terms.', 'church-core'); ?></p>

            <p>
                <a class="button button-secondary" href="<?php echo esc_url($template_url); ?>">
                    <?php esc_html_e('Download Sample CSV', 'church-core'); ?>
                </a>
            </p>

            <h2><?php esc_html_e('CSV Columns', 'church-core'); ?></h2>
            <table class="widefat striped" style="max-width: 960px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Column', 'church-core'); ?></th>
                        <th><?php esc_html_e('Required', 'church-core'); ?></th>
                        <th><?php esc_html_e('Description', 'church-core'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($columns as $key => $settings) : ?>
                        <tr>
                            <td><code><?php echo esc_html($key); ?></code></td>
                            <td><?php echo ! empty($settings['required']) ? esc_html__('Yes', 'church-core') : esc_html__('No', 'church-core'); ?></td>
                            <td><?php echo esc_html((string) ($settings['description'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <ul style="margin-top: 16px; max-width: 960px;">
                <li><?php esc_html_e('Keep the header row exactly as downloaded. The importer expects the same column order.', 'church-core'); ?></li>
                <li><?php esc_html_e('Use dates in YYYY-MM-DD format.', 'church-core'); ?></li>
                <li><?php esc_html_e('Use one speaker and one series name per row.', 'church-core'); ?></li>
                <li><?php esc_html_e('Use quoted CSV cells when summary notes contain commas or line breaks.', 'church-core'); ?></li>
            </ul>

            <h2><?php esc_html_e('Import File', 'church-core'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="church_core_sermon_csv_import">
                <?php wp_nonce_field('church_core_sermon_csv_import'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="church-core-sermon-csv"><?php esc_html_e('CSV File', 'church-core'); ?></label>
                            </th>
                            <td>
                                <input type="file" id="church-core-sermon-csv" name="sermon_csv" accept=".csv,text/csv" required>
                                <p class="description"><?php esc_html_e('Upload a .csv file that matches the sample schema.', 'church-core'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(__('Import Sermons', 'church-core')); ?>
            </form>
        </div>
        <?php
    }

    public static function handle_template_download(): void
    {
        self::assert_permissions();
        check_admin_referer('church_core_sermon_csv_template');

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=sermon-import-sample.csv');

        $handle = fopen('php://output', 'wb');

        if ($handle === false) {
            wp_die(esc_html__('Unable to create the sample CSV file.', 'church-core'));
        }

        $headers = array_keys(self::get_csv_columns());
        $sample_row = self::get_sample_row();
        $row = [];

        foreach ($headers as $header) {
            $row[] = (string) ($sample_row[$header] ?? '');
        }

        fputcsv($handle, $headers);
        fputcsv($handle, $row);
        fclose($handle);
        exit;
    }

    public static function handle_import(): void
    {
        self::assert_permissions();
        check_admin_referer('church_core_sermon_csv_import');

        $result = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        if (! isset($_FILES['sermon_csv']) || ! is_array($_FILES['sermon_csv'])) {
            $result['errors'][] = __('Choose a CSV file to import.', 'church-core');
            self::persist_result($result);
            wp_safe_redirect(self::get_import_page_url());
            exit;
        }

        $file = $_FILES['sermon_csv'];
        $file_name = isset($file['name']) ? strtolower((string) $file['name']) : '';
        $file_error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
        $tmp_name = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';

        if ($file_error !== UPLOAD_ERR_OK || $tmp_name === '') {
            $result['errors'][] = __('The uploaded CSV file could not be processed.', 'church-core');
            self::persist_result($result);
            wp_safe_redirect(self::get_import_page_url());
            exit;
        }

        if (! str_ends_with($file_name, '.csv')) {
            $result['errors'][] = __('Upload a file with a .csv extension.', 'church-core');
            self::persist_result($result);
            wp_safe_redirect(self::get_import_page_url());
            exit;
        }

        $parsed = self::read_csv_rows($tmp_name);

        if (($parsed['errors'] ?? []) !== []) {
            $result['errors'] = array_merge($result['errors'], $parsed['errors']);
            self::persist_result($result);
            wp_safe_redirect(self::get_import_page_url());
            exit;
        }

        $header_errors = self::validate_header($parsed['header'] ?? []);

        if ($header_errors !== []) {
            $result['errors'] = array_merge($result['errors'], $header_errors);
            self::persist_result($result);
            wp_safe_redirect(self::get_import_page_url());
            exit;
        }

        $rows = $parsed['rows'] ?? [];

        if ($rows === []) {
            $result['errors'][] = __('No sermon rows were found in the uploaded CSV.', 'church-core');
            self::persist_result($result);
            wp_safe_redirect(self::get_import_page_url());
            exit;
        }

        $column_count = count(self::get_csv_columns());
        $seen_duplicate_keys = [];

        foreach ($rows as $row_data) {
            $line_number = (int) ($row_data['line_number'] ?? 0);
            $raw_row = isset($row_data['values']) && is_array($row_data['values']) ? $row_data['values'] : [];

            if (count($raw_row) !== $column_count) {
                $result['skipped']++;
                $result['errors'][] = sprintf(
                    __('Line %1$d: Expected %2$d columns but found %3$d.', 'church-core'),
                    $line_number,
                    $column_count,
                    count($raw_row)
                );
                continue;
            }

            $row = self::normalize_row($raw_row);
            $validation_errors = self::validate_row($row);

            if ($validation_errors !== []) {
                $result['skipped']++;

                foreach ($validation_errors as $message) {
                    $result['errors'][] = sprintf(__('Line %1$d: %2$s', 'church-core'), $line_number, $message);
                }

                continue;
            }

            $duplicate_key = self::find_duplicate_key($row);

            if (isset($seen_duplicate_keys[$duplicate_key])) {
                $result['skipped']++;
                $result['errors'][] = sprintf(
                    __('Line %1$d: Duplicate row in this CSV. The same sermon already appeared on line %2$d.', 'church-core'),
                    $line_number,
                    (int) $seen_duplicate_keys[$duplicate_key]
                );
                continue;
            }

            $seen_duplicate_keys[$duplicate_key] = $line_number;

            $existing_post_id = self::find_existing_sermon($row);

            if ($existing_post_id !== null) {
                $result['skipped']++;
                $result['errors'][] = sprintf(
                    __('Line %1$d: A matching sermon already exists (post ID %2$d). Imports do not update existing sermons.', 'church-core'),
                    $line_number,
                    $existing_post_id
                );
                continue;
            }

            $term_ids = [];

            foreach (['speaker', 'series'] as $taxonomy) {
                if ($row[$taxonomy] === '') {
                    continue;
                }

                $term_id = self::resolve_term_id($taxonomy, $row[$taxonomy]);

                if (is_wp_error($term_id)) {
                    $result['skipped']++;
                    $result['errors'][] = sprintf(
                        __('Line %1$d: %2$s', 'church-core'),
                        $line_number,
                        $term_id->get_error_message()
                    );
                    continue 2;
                }

                $term_ids[$taxonomy] = [$term_id];
            }

            $post_data = [
                'post_type' => 'sermon',
                'post_status' => 'publish',
                'post_title' => $row['title'],
                'post_excerpt' => $row['excerpt'],
                'post_content' => $row['summary_notes'],
                'post_author' => get_current_user_id(),
            ];

            if ($row['slug'] !== '') {
                $post_data['post_name'] = $row['slug'];
            }

            $post_id = wp_insert_post($post_data, true);

            if (is_wp_error($post_id)) {
                $result['skipped']++;
                $result['errors'][] = sprintf(
                    __('Line %1$d: %2$s', 'church-core'),
                    $line_number,
                    $post_id->get_error_message()
                );
                continue;
            }

            foreach ($term_ids as $taxonomy => $ids) {
                $set_terms = wp_set_object_terms($post_id, $ids, $taxonomy, false);

                if (is_wp_error($set_terms)) {
                    wp_delete_post($post_id, true);
                    $result['skipped']++;
                    $result['errors'][] = sprintf(
                        __('Line %1$d: %2$s', 'church-core'),
                        $line_number,
                        $set_terms->get_error_message()
                    );
                    continue 2;
                }
            }

            update_post_meta($post_id, 'sermon_date', $row['sermon_date']);

            foreach (['scripture_reference', 'youtube_url', 'audio_url'] as $meta_key) {
                if ($row[$meta_key] === '') {
                    continue;
                }

                update_post_meta($post_id, $meta_key, $row[$meta_key]);
            }

            $result['imported']++;
        }

        self::persist_result($result);
        wp_safe_redirect(self::get_import_page_url());
        exit;
    }

    private static function get_csv_columns(): array
    {
        return [
            'title' => [
                'required' => true,
                'description' => __('The sermon title.', 'church-core'),
            ],
            'slug' => [
                'required' => false,
                'description' => __('Optional URL slug. Leave blank to let WordPress generate one.', 'church-core'),
            ],
            'sermon_date' => [
                'required' => true,
                'description' => __('Required date in YYYY-MM-DD format.', 'church-core'),
            ],
            'speaker' => [
                'required' => false,
                'description' => __('Optional speaker name. Missing speakers are created automatically.', 'church-core'),
            ],
            'series' => [
                'required' => false,
                'description' => __('Optional series name. Missing series are created automatically.', 'church-core'),
            ],
            'scripture_reference' => [
                'required' => false,
                'description' => __('Optional scripture reference such as Romans 8:1-17.', 'church-core'),
            ],
            'youtube_url' => [
                'required' => false,
                'description' => __('Optional YouTube URL.', 'church-core'),
            ],
            'audio_url' => [
                'required' => false,
                'description' => __('Optional audio file URL.', 'church-core'),
            ],
            'excerpt' => [
                'required' => false,
                'description' => __('Optional short summary used in archive cards.', 'church-core'),
            ],
            'summary_notes' => [
                'required' => false,
                'description' => __('Optional full sermon notes or rich-text summary content.', 'church-core'),
            ],
        ];
    }

    private static function get_sample_row(): array
    {
        return [
            'title' => 'Romans 8: Hope in Christ',
            'slug' => 'romans-8-hope-in-christ',
            'sermon_date' => '2026-04-19',
            'speaker' => 'Joshua Abraham',
            'series' => 'Romans',
            'scripture_reference' => 'Romans 8:1-17',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'audio_url' => 'https://example.com/audio/romans-8-hope-in-christ.mp3',
            'excerpt' => 'A short summary for sermon cards and archive previews.',
            'summary_notes' => "<p>Full sermon notes or summary content go here.</p>\n<p>Use quoted CSV cells when notes contain commas or line breaks.</p>",
        ];
    }

    private static function get_import_page_url(): string
    {
        return admin_url('edit.php?post_type=sermon&page=' . self::PAGE_SLUG);
    }

    private static function assert_permissions(): void
    {
        $post_type_object = get_post_type_object('sermon');
        $publish_cap = $post_type_object instanceof WP_Post_Type ? $post_type_object->cap->publish_posts : 'publish_posts';

        if (! current_user_can('manage_categories') || ! current_user_can($publish_cap)) {
            wp_die(
                esc_html__('You do not have permission to import sermons.', 'church-core'),
                esc_html__('Forbidden', 'church-core'),
                ['response' => 403]
            );
        }
    }

    private static function normalize_header(array $header): array
    {
        $normalized = array_map(
            static function ($value): string {
                return trim((string) $value);
            },
            $header
        );

        if (isset($normalized[0])) {
            $normalized[0] = (string) preg_replace('/^\xEF\xBB\xBF/', '', $normalized[0]);
        }

        return $normalized;
    }

    private static function validate_header(array $header): array
    {
        $expected = array_keys(self::get_csv_columns());

        if ($header === $expected) {
            return [];
        }

        return [
            sprintf(
                __('The CSV header row must exactly match: %s.', 'church-core'),
                implode(', ', $expected)
            ),
        ];
    }

    private static function read_csv_rows(string $file_path): array
    {
        $handle = fopen($file_path, 'rb');

        if ($handle === false) {
            return [
                'header' => [],
                'rows' => [],
                'errors' => [__('The uploaded CSV file could not be opened.', 'church-core')],
            ];
        }

        $header = [];
        $rows = [];
        $line_number = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $line_number++;

            if ($line_number === 1) {
                $header = self::normalize_header($row);
                continue;
            }

            $is_blank_row = true;

            foreach ($row as $value) {
                if (trim((string) $value) !== '') {
                    $is_blank_row = false;
                    break;
                }
            }

            if ($is_blank_row) {
                continue;
            }

            $rows[] = [
                'line_number' => $line_number,
                'values' => $row,
            ];
        }

        $errors = [];

        if (! feof($handle)) {
            $errors[] = __('The CSV file could not be read completely.', 'church-core');
        }

        fclose($handle);

        return [
            'header' => $header,
            'rows' => $rows,
            'errors' => $errors,
        ];
    }

    private static function normalize_row(array $row): array
    {
        $headers = array_keys(self::get_csv_columns());
        $raw_values = [];
        $normalized = [];

        foreach ($headers as $index => $header) {
            $raw_value = trim((string) ($row[$index] ?? ''));
            $raw_values[$header] = $raw_value;

            if ($header === 'summary_notes') {
                $normalized[$header] = $raw_value === '' ? '' : wp_kses_post($raw_value);
                continue;
            }

            if ($header === 'excerpt') {
                $normalized[$header] = sanitize_textarea_field($raw_value);
                continue;
            }

            if (in_array($header, ['youtube_url', 'audio_url'], true)) {
                $normalized[$header] = $raw_value === '' ? '' : esc_url_raw($raw_value);
                continue;
            }

            if ($header === 'slug') {
                $normalized[$header] = $raw_value === '' ? '' : sanitize_title($raw_value);
                continue;
            }

            $normalized[$header] = sanitize_text_field($raw_value);
        }

        $normalized['_raw'] = $raw_values;

        return $normalized;
    }

    private static function validate_row(array $row): array
    {
        $errors = [];
        $raw_values = isset($row['_raw']) && is_array($row['_raw']) ? $row['_raw'] : [];

        if ($row['title'] === '') {
            $errors[] = __('Title is required.', 'church-core');
        }

        if ($row['sermon_date'] === '') {
            $errors[] = __('Sermon date is required.', 'church-core');
        } elseif (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['sermon_date']) || ! self::is_valid_date($row['sermon_date'])) {
            $errors[] = __('Sermon date must be a valid date in YYYY-MM-DD format.', 'church-core');
        }

        if (($raw_values['slug'] ?? '') !== '' && $row['slug'] === '') {
            $errors[] = __('Slug could not be converted into a valid value.', 'church-core');
        }

        foreach (['youtube_url' => __('YouTube URL', 'church-core'), 'audio_url' => __('Audio URL', 'church-core')] as $field => $label) {
            if (($raw_values[$field] ?? '') === '') {
                continue;
            }

            if ($row[$field] === '' || ! wp_http_validate_url($row[$field])) {
                $errors[] = sprintf(__('%s must be a valid http or https URL.', 'church-core'), $label);
            }
        }

        return $errors;
    }

    private static function find_duplicate_key(array $row): string
    {
        if ($row['slug'] !== '') {
            return 'slug:' . $row['slug'];
        }

        return 'title-date:' . sanitize_title($row['title']) . '|' . $row['sermon_date'];
    }

    private static function find_existing_sermon(array $row): ?int
    {
        if ($row['slug'] !== '') {
            $posts = get_posts([
                'name' => $row['slug'],
                'post_type' => 'sermon',
                'post_status' => 'any',
                'posts_per_page' => 1,
                'fields' => 'ids',
            ]);

            if ($posts !== []) {
                return (int) $posts[0];
            }
        }

        global $wpdb;

        $post_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT p.ID
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm
                    ON pm.post_id = p.ID
                    AND pm.meta_key = 'sermon_date'
                WHERE p.post_type = 'sermon'
                    AND p.post_status NOT IN ('trash', 'auto-draft', 'inherit')
                    AND p.post_title = %s
                    AND pm.meta_value = %s
                LIMIT 1",
                $row['title'],
                $row['sermon_date']
            )
        );

        if ($post_id === null) {
            return null;
        }

        $post_id = (int) $post_id;

        return $post_id > 0 ? $post_id : null;
    }

    private static function resolve_term_id(string $taxonomy, string $term_name)
    {
        $existing = term_exists($term_name, $taxonomy);

        if (is_array($existing) && isset($existing['term_id'])) {
            return (int) $existing['term_id'];
        }

        if (is_string($existing) || is_int($existing)) {
            return (int) $existing;
        }

        $inserted = wp_insert_term($term_name, $taxonomy);

        if (is_wp_error($inserted)) {
            return new WP_Error(
                'church_core_sermon_import_term',
                sprintf(__('Could not create the %1$s term "%2$s": %3$s', 'church-core'), $taxonomy, $term_name, $inserted->get_error_message())
            );
        }

        return (int) $inserted['term_id'];
    }

    private static function persist_result(array $result): void
    {
        set_transient(
            self::RESULT_TRANSIENT_PREFIX . get_current_user_id(),
            $result,
            self::RESULT_TTL
        );
    }

    private static function consume_result(): ?array
    {
        $key = self::RESULT_TRANSIENT_PREFIX . get_current_user_id();
        $result = get_transient($key);

        if (! is_array($result)) {
            return null;
        }

        delete_transient($key);

        return $result;
    }

    private static function is_valid_date(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if (! $date instanceof DateTimeImmutable) {
            return false;
        }

        if (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
            return false;
        }

        return $date->format('Y-m-d') === $value;
    }
}
