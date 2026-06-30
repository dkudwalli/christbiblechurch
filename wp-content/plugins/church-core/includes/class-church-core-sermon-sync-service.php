<?php
if (!defined('ABSPATH')) {
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
            if (!$this->should_import_video($video)) {
                $result['skipped']++;
                continue;
            }

            $scripture_reference = Church_Core_Scripture_Extractor::from_title((string) $video['title']);

            $existing_post_id = $this->find_existing_post_by_video_id((string) $video['video_id']);

            if ($existing_post_id > 0) {
                if ($this->maybe_backfill_scripture_reference((int) $existing_post_id, $scripture_reference)) {
                    $result['backfilled']++;
                }

                $result['skipped']++;
                continue;
            }

            $legacy_post_id = $this->find_existing_post_by_youtube_url((string) $video['video_id']);

            if ($legacy_post_id > 0) {
                update_post_meta($legacy_post_id, Church_Core_Sermons::YOUTUBE_VIDEO_ID_META_KEY, (string) $video['video_id']);

                $this->maybe_backfill_scripture_reference((int) $legacy_post_id, $scripture_reference);
                $result['backfilled']++;
                $result['skipped']++;
                continue;
            }

            $created_post_id = $this->create_sermon_post($video, $scripture_reference);

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
        $live_broadcast_content = isset($video['live_broadcast_content']) ? trim((string) $video['live_broadcast_content']) : 'none';
        $actual_end_time = isset($video['actual_end_time']) ? trim((string) $video['actual_end_time']) : '';

        if ($video_id === '' || $title === '' || $published_at === '') {
            return false;
        }

        if ($privacy_status !== '' && $privacy_status !== 'public') {
            return false;
        }

        // Skip scheduled or in-progress livestreams: they are public and titled
        // before the message exists, so importing them would publish a sermon
        // prematurely. A finished broadcast reverts to 'none' and/or carries an
        // actualEndTime, so completed streams still import normally.
        if ($live_broadcast_content !== '' && $live_broadcast_content !== 'none' && $actual_end_time === '') {
            return false;
        }

        if (in_array(strtolower($title), ['deleted video', 'private video'], true)) {
            return false;
        }

        return true;
    }

    private function create_sermon_post(array $video, string $scripture_reference)
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

        $meta_input = [
            Church_Core_Sermons::SERMON_DATE_META_KEY => $dates['sermon_date'],
            Church_Core_Sermons::YOUTUBE_VIDEO_ID_META_KEY => (string) $video['video_id'],
            Church_Core_Sermons::YOUTUBE_URL_META_KEY => (string) $video['youtube_url'],
        ];

        if ($scripture_reference !== '') {
            $meta_input[Church_Core_Sermons::SCRIPTURE_REFERENCE_META_KEY] = $scripture_reference;
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
            'meta_input' => $meta_input,
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

        if (Church_Core_Sermon_Cron::has_default_speaker_setting()) {
            $speaker_term_id = Church_Core_Sermon_Cron::get_default_speaker_term_id();
            $speaker_term = $speaker_term_id > 0 ? get_term($speaker_term_id, 'speaker') : null;

            if (! $speaker_term instanceof WP_Term || is_wp_error($speaker_term)) {
                return new WP_Error(
                    'church_core_sermon_sync_invalid_default_speaker',
                    __('The saved default speaker for YouTube sync no longer exists. Choose a new default speaker in the YouTube Sync settings.', 'church-core')
                );
            }

            $cached_term_id = $speaker_term_id;

            return $cached_term_id;
        }

        $speaker_name = (string) apply_filters('church_core_sermon_sync_default_speaker', 'Unknown');
        $speaker_name = sanitize_text_field($speaker_name);

        if ($speaker_name === '') {
            return new WP_Error(
                'church_core_sermon_sync_missing_speaker',
                __('The default speaker name for YouTube sync is empty.', 'church-core')
            );
        }

        $term_id = Church_Core_Term_Helper::ensure_term($speaker_name, 'speaker');

        if (is_wp_error($term_id)) {
            return new WP_Error(
                'church_core_sermon_sync_speaker_insert_failed',
                sprintf(
                    __('Could not create the default speaker term "%1$s": %2$s', 'church-core'),
                    $speaker_name,
                    $term_id->get_error_message()
                )
            );
        }

        $cached_term_id = $term_id;

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

        if (!is_array($words) || count($words) <= $max_words) {
            return $description;
        }

        return rtrim(implode(' ', array_slice($words, 0, $max_words)), " \t\n\r\0\x0B,.;:-") . '...';
    }

    private function maybe_backfill_scripture_reference(int $post_id, string $scripture_reference): bool
    {
        if ($scripture_reference === '') {
            return false;
        }

        if ((string) get_post_meta($post_id, Church_Core_Sermons::SCRIPTURE_REFERENCE_META_KEY, true) !== '') {
            return false;
        }

        update_post_meta($post_id, Church_Core_Sermons::SCRIPTURE_REFERENCE_META_KEY, $scripture_reference);

        return true;
    }

    private function find_existing_post_by_video_id(string $video_id): int
    {
        $posts = get_posts([
            'post_type' => 'sermon',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => Church_Core_Sermons::YOUTUBE_VIDEO_ID_META_KEY,
                    'value' => $video_id,
                ]
            ],
        ]);

        if ($posts === []) {
            return 0;
        }

        return (int) $posts[0];
    }

    private function find_existing_post_by_youtube_url(string $video_id): int
    {
        // Unbounded: the LIKE on an 11-char video id is a cheap prefilter that matches
        // ~0-1 rows, and each candidate is confirmed by exact id below — a low cap
        // (was 10) could silently skip the real legacy post during backfill.
        $posts = get_posts([
            'post_type' => 'sermon',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => Church_Core_Sermons::YOUTUBE_URL_META_KEY,
                    'value' => $video_id,
                    'compare' => 'LIKE',
                ]
            ],
        ]);

        foreach ($posts as $post_id) {
            $youtube_url = (string) get_post_meta((int) $post_id, Church_Core_Sermons::YOUTUBE_URL_META_KEY, true);

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
            'meta_query' => [
                ['key' => Church_Core_Sermons::YOUTUBE_URL_META_KEY, 'compare' => 'EXISTS'],
                ['key' => Church_Core_Sermons::YOUTUBE_VIDEO_ID_META_KEY, 'compare' => 'NOT EXISTS'],
            ],
        ]);

        $backfilled = 0;

        foreach ($post_ids as $post_id) {
            $youtube_url = (string) get_post_meta((int) $post_id, Church_Core_Sermons::YOUTUBE_URL_META_KEY, true);
            $video_id = Church_Core_Youtube_Client::extract_video_id_from_url($youtube_url);

            if ($video_id === '') {
                continue;
            }

            update_post_meta((int) $post_id, Church_Core_Sermons::YOUTUBE_VIDEO_ID_META_KEY, $video_id);
            $backfilled++;
        }

        return $backfilled;
    }
}
