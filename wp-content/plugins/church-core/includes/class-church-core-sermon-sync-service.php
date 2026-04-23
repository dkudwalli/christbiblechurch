<?php
if (! defined('ABSPATH')) {
    exit;
}

final class Church_Core_Sermon_Sync_Service
{
    private const CONTENT_WORD_LIMIT = 400;
    private const EXCERPT_WORD_LIMIT = 40;

    private Church_Core_Youtube_Client $youtube_client;

    public function __construct(Church_Core_Youtube_Client $youtube_client)
    {
        $this->youtube_client = $youtube_client;
    }

    public function sync_recent_sermons(): array
    {
        $result = $this->get_default_result();
        $result['backfilled'] += $this->backfill_existing_video_ids();

        $max_results = (int) apply_filters('church_core_sermon_sync_max_results', 25);
        $videos = $this->youtube_client->fetch_recent_channel_videos(max(1, $max_results));

        if (is_wp_error($videos)) {
            $result['status'] = 'error';
            $result['message'] = $videos->get_error_message();
            $result['errors'][] = $videos->get_error_message();

            return $result;
        }

        if ($videos === []) {
            $result['message'] = __('No YouTube uploads were available to sync.', 'church-core');

            return $result;
        }

        foreach ($videos as $video) {
            if (! $this->should_import_video($video)) {
                $result['skipped']++;
                continue;
            }

            $existing_post_id = $this->find_existing_post_by_video_id((string) $video['video_id']);

            if ($existing_post_id > 0) {
                $result['skipped']++;
                continue;
            }

            $legacy_post_id = $this->find_existing_post_by_youtube_url((string) $video['video_id']);

            if ($legacy_post_id > 0) {
                update_post_meta($legacy_post_id, 'youtube_video_id', (string) $video['video_id']);
                $result['backfilled']++;
                $result['skipped']++;
                continue;
            }

            $created_post_id = $this->create_sermon_post($video);

            if (is_wp_error($created_post_id)) {
                $result['status'] = 'error';
                $result['errors'][] = $created_post_id->get_error_message();
                continue;
            }

            $result['created']++;
        }

        if ($result['errors'] !== []) {
            $result['status'] = $result['created'] > 0 || $result['backfilled'] > 0 ? 'partial' : 'error';
        }

        $result['message'] = sprintf(
            __('Created %1$d sermons, backfilled %2$d existing records, and skipped %3$d duplicates or unsupported videos.', 'church-core'),
            $result['created'],
            $result['backfilled'],
            $result['skipped']
        );

        return $result;
    }

    private function get_default_result(): array
    {
        return [
            'status' => 'success',
            'message' => '',
            'created' => 0,
            'backfilled' => 0,
            'skipped' => 0,
            'errors' => [],
        ];
    }

    private function should_import_video(array $video): bool
    {
        $video_id = isset($video['video_id']) ? trim((string) $video['video_id']) : '';
        $title = isset($video['title']) ? trim((string) $video['title']) : '';
        $published_at = isset($video['published_at']) ? trim((string) $video['published_at']) : '';
        $privacy_status = isset($video['privacy_status']) ? trim((string) $video['privacy_status']) : '';

        if ($video_id === '' || $title === '' || $published_at === '') {
            return false;
        }

        if ($privacy_status !== '' && $privacy_status !== 'public') {
            return false;
        }

        if (in_array(strtolower($title), ['deleted video', 'private video'], true)) {
            return false;
        }

        return true;
    }

    private function create_sermon_post(array $video)
    {
        $dates = $this->build_post_dates((string) $video['published_at']);

        if (is_wp_error($dates)) {
            return $dates;
        }

        $summary = $this->build_summary((string) ($video['description'] ?? ''), self::CONTENT_WORD_LIMIT);
        $excerpt = $this->build_summary($summary, self::EXCERPT_WORD_LIMIT);
        $slug = sanitize_title((string) $video['title']);

        if ($slug === '') {
            $slug = 'youtube-video-' . sanitize_title((string) $video['video_id']);
        }

        $post_id = wp_insert_post([
            'post_type' => 'sermon',
            'post_status' => 'publish',
            'post_title' => (string) $video['title'],
            'post_name' => $slug,
            'post_excerpt' => $excerpt,
            'post_content' => $summary,
            'post_date' => $dates['post_date'],
            'post_date_gmt' => $dates['post_date_gmt'],
            'meta_input' => [
                'sermon_date' => $dates['sermon_date'],
                'youtube_video_id' => (string) $video['video_id'],
                'youtube_url' => (string) $video['youtube_url'],
            ],
        ], true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        $speaker_term_id = $this->resolve_default_speaker_term_id();

        if (is_wp_error($speaker_term_id)) {
            wp_delete_post($post_id, true);

            return $speaker_term_id;
        }

        $terms_result = wp_set_object_terms($post_id, [$speaker_term_id], 'speaker', false);

        if (is_wp_error($terms_result)) {
            wp_delete_post($post_id, true);

            return new WP_Error(
                'church_core_sermon_sync_speaker_failed',
                sprintf(
                    __('Could not assign the default speaker: %s', 'church-core'),
                    $terms_result->get_error_message()
                )
            );
        }

        return (int) $post_id;
    }

    private function resolve_default_speaker_term_id()
    {
        static $cached_term_id = null;

        if (is_int($cached_term_id) && $cached_term_id > 0) {
            return $cached_term_id;
        }

        $speaker_name = (string) apply_filters('church_core_sermon_sync_default_speaker', 'Pastor Benji');
        $speaker_name = sanitize_text_field($speaker_name);

        if ($speaker_name === '') {
            return new WP_Error(
                'church_core_sermon_sync_missing_speaker',
                __('The default speaker name for YouTube sync is empty.', 'church-core')
            );
        }

        $existing_term = term_exists($speaker_name, 'speaker');

        if (is_array($existing_term) && isset($existing_term['term_id'])) {
            $cached_term_id = (int) $existing_term['term_id'];

            return $cached_term_id;
        }

        if (is_string($existing_term) || is_int($existing_term)) {
            $cached_term_id = (int) $existing_term;

            return $cached_term_id;
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

        $cached_term_id = (int) $inserted_term['term_id'];

        return $cached_term_id;
    }

    private function build_post_dates(string $published_at)
    {
        try {
            $utc_date = new DateTimeImmutable($published_at, new DateTimeZone('UTC'));
        } catch (Exception $exception) {
            return new WP_Error(
                'church_core_sermon_sync_invalid_date',
                sprintf(
                    __('The YouTube publish date could not be parsed: %s', 'church-core'),
                    $exception->getMessage()
                )
            );
        }

        $local_date = $utc_date->setTimezone(wp_timezone());

        return [
            'post_date' => $local_date->format('Y-m-d H:i:s'),
            'post_date_gmt' => $utc_date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'sermon_date' => $local_date->format('Y-m-d'),
        ];
    }

    private function build_summary(string $description, int $max_words): string
    {
        $description = html_entity_decode($description, ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8');
        $description = preg_replace('~(?:https?://|www\.)\S+~iu', '', $description) ?: $description;
        $description = preg_replace("/\r\n?/", "\n", $description) ?: $description;
        $description = preg_replace("/[ \t]+/", ' ', $description) ?: $description;
        $description = preg_replace("/\n{3,}/", "\n\n", $description) ?: $description;
        $description = sanitize_textarea_field(trim($description));

        if ($description === '') {
            return '';
        }

        $words = preg_split('/\s+/u', $description, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($words) || count($words) <= $max_words) {
            return $description;
        }

        return rtrim(implode(' ', array_slice($words, 0, $max_words)), " \t\n\r\0\x0B,.;:-") . '...';
    }

    private function find_existing_post_by_video_id(string $video_id): int
    {
        $posts = get_posts([
            'post_type' => 'sermon',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [[
                'key' => 'youtube_video_id',
                'value' => $video_id,
            ]],
        ]);

        if ($posts === []) {
            return 0;
        }

        return (int) $posts[0];
    }

    private function find_existing_post_by_youtube_url(string $video_id): int
    {
        $posts = get_posts([
            'post_type' => 'sermon',
            'post_status' => 'any',
            'posts_per_page' => 10,
            'fields' => 'ids',
            'meta_query' => [[
                'key' => 'youtube_url',
                'value' => $video_id,
                'compare' => 'LIKE',
            ]],
        ]);

        foreach ($posts as $post_id) {
            $youtube_url = (string) get_post_meta((int) $post_id, 'youtube_url', true);

            if (Church_Core_Youtube_Client::extract_video_id_from_url($youtube_url) === $video_id) {
                return (int) $post_id;
            }
        }

        return 0;
    }

    private function backfill_existing_video_ids(): int
    {
        $post_ids = get_posts([
            'post_type' => 'sermon',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [[
                'key' => 'youtube_url',
                'value' => '',
                'compare' => '!=',
            ]],
        ]);

        $backfilled = 0;

        foreach ($post_ids as $post_id) {
            if ((string) get_post_meta((int) $post_id, 'youtube_video_id', true) !== '') {
                continue;
            }

            $youtube_url = (string) get_post_meta((int) $post_id, 'youtube_url', true);
            $video_id = Church_Core_Youtube_Client::extract_video_id_from_url($youtube_url);

            if ($video_id === '') {
                continue;
            }

            update_post_meta((int) $post_id, 'youtube_video_id', $video_id);
            $backfilled++;
        }

        return $backfilled;
    }
}
