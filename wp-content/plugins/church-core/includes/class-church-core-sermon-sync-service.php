<?php
if (!defined('ABSPATH')) {
    exit;
}

final class Church_Core_Sermon_Sync_Service
{
    private const CONTENT_WORD_LIMIT = 400;
    private const EXCERPT_WORD_LIMIT = 40;
    private const SCRIPTURE_BOOK_ALIASES = [
        'Genesis' => ['Genesis', 'Gen', 'Ge', 'Gn'],
        'Exodus' => ['Exodus', 'Exod', 'Exo', 'Ex'],
        'Leviticus' => ['Leviticus', 'Lev', 'Le', 'Lv'],
        'Numbers' => ['Numbers', 'Num', 'Nu', 'Nm', 'Nb'],
        'Deuteronomy' => ['Deuteronomy', 'Deut', 'Deu', 'Dt'],
        'Joshua' => ['Joshua', 'Josh', 'Jos', 'Jsh'],
        'Judges' => ['Judges', 'Judg', 'Jdg', 'Jdgs'],
        'Ruth' => ['Ruth', 'Rth', 'Ru'],
        '1 Samuel' => ['1 Samuel', '1 Sam', '1Sa', '1 Sa'],
        '2 Samuel' => ['2 Samuel', '2 Sam', '2Sa', '2 Sa'],
        '1 Kings' => ['1 Kings', '1 Kgs', '1 Ki', '1Ki', '1 Kngs'],
        '2 Kings' => ['2 Kings', '2 Kgs', '2 Ki', '2Ki', '2 Kngs'],
        '1 Chronicles' => ['1 Chronicles', '1 Chron', '1 Chr', '1Ch', '1 Chrn'],
        '2 Chronicles' => ['2 Chronicles', '2 Chron', '2 Chr', '2Ch', '2 Chrn'],
        'Ezra' => ['Ezra', 'Ezr'],
        'Nehemiah' => ['Nehemiah', 'Neh', 'Ne'],
        'Esther' => ['Esther', 'Esth', 'Est'],
        'Job' => ['Job'],
        'Psalms' => ['Psalms', 'Psalm', 'Ps'],
        'Proverbs' => ['Proverbs', 'Prov', 'Pro', 'Prv', 'Pr'],
        'Ecclesiastes' => ['Ecclesiastes', 'Eccles', 'Eccl', 'Ecc', 'Qoh'],
        'Song of Solomon' => ['Song of Solomon', 'Song of Songs', 'Song', 'SOS', 'Canticles'],
        'Isaiah' => ['Isaiah', 'Isa'],
        'Jeremiah' => ['Jeremiah', 'Jer'],
        'Lamentations' => ['Lamentations', 'Lam'],
        'Ezekiel' => ['Ezekiel', 'Ezek', 'Eze', 'Ezk'],
        'Daniel' => ['Daniel', 'Dan', 'Da', 'Dn'],
        'Hosea' => ['Hosea', 'Hos', 'Ho'],
        'Joel' => ['Joel', 'Joe', 'Jl'],
        'Amos' => ['Amos', 'Am'],
        'Obadiah' => ['Obadiah', 'Obad', 'Ob'],
        'Jonah' => ['Jonah', 'Jon'],
        'Micah' => ['Micah', 'Mic'],
        'Nahum' => ['Nahum', 'Nah'],
        'Habakkuk' => ['Habakkuk', 'Hab'],
        'Zephaniah' => ['Zephaniah', 'Zeph', 'Zep'],
        'Haggai' => ['Haggai', 'Hag', 'Hg'],
        'Zechariah' => ['Zechariah', 'Zech', 'Zec'],
        'Malachi' => ['Malachi', 'Mal'],
        'Matthew' => ['Matthew', 'Matt', 'Mt'],
        'Mark' => ['Mark', 'Mrk', 'Mk', 'Mr'],
        'Luke' => ['Luke', 'Luk', 'Lk'],
        'John' => ['John', 'Jn', 'Jhn'],
        'Acts' => ['Acts', 'Act', 'Ac'],
        'Romans' => ['Romans', 'Rom', 'Ro', 'Rm'],
        '1 Corinthians' => ['1 Corinthians', '1 Cor', '1Co', '1 Co'],
        '2 Corinthians' => ['2 Corinthians', '2 Cor', '2Co', '2 Co'],
        'Galatians' => ['Galatians', 'Gal', 'Ga'],
        'Ephesians' => ['Ephesians', 'Eph'],
        'Philippians' => ['Philippians', 'Phil', 'Php', 'Pp'],
        'Colossians' => ['Colossians', 'Col', 'Colos', 'Coloss'],
        '1 Thessalonians' => ['1 Thessalonians', '1 Thess', '1 Thes', '1Th', '1 Th'],
        '2 Thessalonians' => ['2 Thessalonians', '2 Thess', '2 Thes', '2Th', '2 Th'],
        '1 Timothy' => ['1 Timothy', '1 Tim', '1Ti', '1 Ti'],
        '2 Timothy' => ['2 Timothy', '2 Tim', '2Ti', '2 Ti'],
        'Titus' => ['Titus', 'Tit'],
        'Philemon' => ['Philemon', 'Phlm', 'Phm', 'Pm'],
        'Hebrews' => ['Hebrews', 'Heb'],
        'James' => ['James', 'Jas', 'Jm'],
        '1 Peter' => ['1 Peter', '1 Pet', '1Pe', '1 Pt'],
        '2 Peter' => ['2 Peter', '2 Pet', '2Pe', '2 Pt'],
        '1 John' => ['1 John', '1 Jn', '1Jn', '1 Jhn'],
        '2 John' => ['2 John', '2 Jn', '2Jn', '2 Jhn'],
        '3 John' => ['3 John', '3 Jn', '3Jn', '3 Jhn'],
        'Jude' => ['Jude', 'Jud'],
        'Revelation' => ['Revelation', 'Rev', 'Re', 'Revelations'],
    ];

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

            $scripture_reference = $this->extract_scripture_reference_from_title((string) $video['title']);

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
                update_post_meta($legacy_post_id, 'youtube_video_id', (string) $video['video_id']);
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
            'sermon_date' => $dates['sermon_date'],
            'youtube_video_id' => (string) $video['video_id'],
            'youtube_url' => (string) $video['youtube_url'],
        ];

        if ($scripture_reference !== '') {
            $meta_input['scripture_reference'] = $scripture_reference;
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

        if (!is_array($words) || count($words) <= $max_words) {
            return $description;
        }

        return rtrim(implode(' ', array_slice($words, 0, $max_words)), " \t\n\r\0\x0B,.;:-") . '...';
    }

    private function extract_scripture_reference_from_title(string $title): string
    {
        $title = html_entity_decode(trim($title), ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8');

        if ($title === '') {
            return '';
        }

        $pattern = sprintf(
            '/(?<![A-Za-z])(?P<book>%1$s)\.?\s*(?P<chapter>\d{1,3})\s*:\s*(?P<verse>\d{1,3})(?:(?P<separator>\s*[-–—]\s*)(?:(?P<end_chapter>\d{1,3})\s*:\s*)?(?P<end_verse>\d{1,3}))?/iu',
            $this->get_scripture_book_pattern()
        );

        if (preg_match($pattern, $title, $matches) !== 1) {
            return '';
        }

        $book_name = $this->expand_scripture_book_name((string) $matches['book']);

        if ($book_name === '') {
            return '';
        }

        $separator = isset($matches['separator']) && $matches['separator'] !== ''
            ? preg_replace('/\s+/u', '', (string) $matches['separator'])
            : '';

        $reference = (string) $matches['chapter'] . ':' . (string) $matches['verse'];

        if (isset($matches['end_verse']) && $matches['end_verse'] !== '') {
            $separator = $separator !== '' ? $separator : '-';
            $reference .= $separator;

            if (isset($matches['end_chapter']) && $matches['end_chapter'] !== '') {
                $reference .= (string) $matches['end_chapter'] . ':';
            }

            $reference .= (string) $matches['end_verse'];
        }

        $scripture_reference = sanitize_text_field($book_name . ' ' . $reference);
        $scripture_reference = (string) apply_filters(
            'church_core_sermon_sync_extract_scripture_reference',
            $scripture_reference,
            $title,
            $matches
        );

        return sanitize_text_field(trim($scripture_reference));
    }

    private function maybe_backfill_scripture_reference(int $post_id, string $scripture_reference): bool
    {
        if ($scripture_reference === '') {
            return false;
        }

        if ((string) get_post_meta($post_id, 'scripture_reference', true) !== '') {
            return false;
        }

        update_post_meta($post_id, 'scripture_reference', $scripture_reference);

        return true;
    }

    private function expand_scripture_book_name(string $book_name): string
    {
        $normalized_book = $this->normalize_scripture_book_alias($book_name);
        $alias_map = $this->get_scripture_book_alias_map();

        return $alias_map[$normalized_book] ?? '';
    }

    private function get_scripture_book_alias_map(): array
    {
        static $alias_map = null;

        if (is_array($alias_map)) {
            return $alias_map;
        }

        $alias_map = [];

        foreach (self::SCRIPTURE_BOOK_ALIASES as $canonical_name => $aliases) {
            $alias_map[$this->normalize_scripture_book_alias($canonical_name)] = $canonical_name;

            foreach ($aliases as $alias) {
                $alias_map[$this->normalize_scripture_book_alias($alias)] = $canonical_name;
            }
        }

        return $alias_map;
    }

    private function get_scripture_book_pattern(): string
    {
        static $pattern = null;

        if (is_string($pattern) && $pattern !== '') {
            return $pattern;
        }

        $aliases = [];

        foreach (self::SCRIPTURE_BOOK_ALIASES as $canonical_name => $book_aliases) {
            $aliases[$canonical_name] = $canonical_name;

            foreach ($book_aliases as $alias) {
                $aliases[$alias] = $alias;
            }
        }

        $aliases = array_values($aliases);

        usort(
            $aliases,
            static function (string $left, string $right): int {
                return strlen($right) <=> strlen($left);
            }
        );

        $pattern_parts = [];

        foreach ($aliases as $alias) {
            $pattern_parts[] = str_replace('\ ', '\s*', preg_quote($alias, '/'));
        }

        $pattern = '(?:' . implode('|', $pattern_parts) . ')';

        return $pattern;
    }

    private function normalize_scripture_book_alias(string $book_name): string
    {
        $book_name = strtolower($book_name);
        $book_name = preg_replace('/\.+/u', '', $book_name) ?: $book_name;
        $book_name = preg_replace('/\s+/u', '', $book_name) ?: $book_name;

        return preg_replace('/[^a-z0-9]/u', '', $book_name) ?: '';
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
                    'key' => 'youtube_video_id',
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
        $posts = get_posts([
            'post_type' => 'sermon',
            'post_status' => 'any',
            'posts_per_page' => 10,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'youtube_url',
                    'value' => $video_id,
                    'compare' => 'LIKE',
                ]
            ],
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
            'meta_query' => [
                [
                    'key' => 'youtube_url',
                    'value' => '',
                    'compare' => '!=',
                ]
            ],
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
