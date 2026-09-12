<?php
if (! defined('ABSPATH')) {
    exit;
}

final class Church_Core_Youtube_Client
{
    private const API_BASE = 'https://www.googleapis.com/youtube/v3/';
    private const PLAYLIST_TRANSIENT_PREFIX = 'church_core_youtube_uploads_';
    private const PLAYLIST_TRANSIENT_TTL = 6 * HOUR_IN_SECONDS;

    private string $api_key;
    private string $channel_id;

    public function __construct(string $api_key, string $channel_id)
    {
        $this->api_key = trim($api_key);
        $this->channel_id = trim($channel_id);
    }

    public function fetch_recent_channel_videos(int $max_results = 25)
    {
        $playlist_id = $this->get_uploads_playlist_id();

        if (is_wp_error($playlist_id)) {
            return $playlist_id;
        }

        $playlist_items = [];
        $page_token = '';
        $remaining = max(1, $max_results);

        while ($remaining > 0) {
            $query_args = [
                'part' => 'snippet,contentDetails',
                'playlistId' => $playlist_id,
                'maxResults' => min(50, $remaining),
            ];

            if ($page_token !== '') {
                $query_args['pageToken'] = $page_token;
            }

            $response = $this->request('playlistItems', $query_args);

            if (is_wp_error($response)) {
                return $response;
            }

            $items = isset($response['items']) && is_array($response['items']) ? $response['items'] : [];
            $playlist_items = array_merge($playlist_items, $items);
            $remaining = $max_results - count($playlist_items);
            $page_token = isset($response['nextPageToken']) ? (string) $response['nextPageToken'] : '';

            if ($page_token === '' || $items === []) {
                break;
            }
        }

        if ($playlist_items === []) {
            return [];
        }

        $video_ids = [];

        foreach ($playlist_items as $item) {
            $video_id = isset($item['contentDetails']['videoId']) ? trim((string) $item['contentDetails']['videoId']) : '';

            if ($video_id !== '') {
                $video_ids[] = $video_id;
            }
        }

        $video_ids = array_values(array_unique($video_ids));

        if ($video_ids === []) {
            return [];
        }

        $videos = $this->fetch_videos_by_ids($video_ids);

        if (is_wp_error($videos)) {
            return $videos;
        }

        $normalized_items = [];

        foreach ($playlist_items as $playlist_item) {
            $video_id = isset($playlist_item['contentDetails']['videoId']) ? trim((string) $playlist_item['contentDetails']['videoId']) : '';

            if ($video_id === '' || ! isset($videos[$video_id])) {
                continue;
            }

            $normalized_items[] = $this->normalize_video($video_id, $videos[$video_id], $playlist_item);
        }

        return $normalized_items;
    }

    public static function extract_video_id_from_url(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $parts = wp_parse_url($url);

        if (! is_array($parts)) {
            return '';
        }

        $host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
        $path = isset($parts['path']) ? trim((string) $parts['path'], '/') : '';

        if ($host === 'youtu.be' && $path !== '') {
            return sanitize_text_field(explode('/', $path)[0]);
        }

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            if ($path === 'watch' && isset($parts['query'])) {
                parse_str((string) $parts['query'], $query_args);

                return isset($query_args['v']) ? sanitize_text_field((string) $query_args['v']) : '';
            }

            if (preg_match('#^(embed|live|shorts)/([^/]+)#', $path, $matches) === 1) {
                return sanitize_text_field((string) $matches[2]);
            }
        }

        return '';
    }

    private function get_uploads_playlist_id()
    {
        $cache_key = self::PLAYLIST_TRANSIENT_PREFIX . md5($this->channel_id);
        $cached = get_transient($cache_key);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = $this->request('channels', [
            'part' => 'contentDetails',
            'id' => $this->channel_id,
            'maxResults' => 1,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $playlist_id = isset($response['items'][0]['contentDetails']['relatedPlaylists']['uploads'])
            ? trim((string) $response['items'][0]['contentDetails']['relatedPlaylists']['uploads'])
            : '';

        if ($playlist_id === '') {
            return new WP_Error(
                'church_core_youtube_missing_uploads_playlist',
                __('Could not resolve the YouTube uploads playlist for the configured channel.', 'church-core')
            );
        }

        set_transient($cache_key, $playlist_id, self::PLAYLIST_TRANSIENT_TTL);

        return $playlist_id;
    }

    private function fetch_videos_by_ids(array $video_ids)
    {
        $response = $this->request('videos', [
            'part' => 'snippet,status,liveStreamingDetails,contentDetails',
            'id' => implode(',', $video_ids),
            'maxResults' => min(50, count($video_ids)),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $items = isset($response['items']) && is_array($response['items']) ? $response['items'] : [];
        $videos = [];

        foreach ($items as $item) {
            $video_id = isset($item['id']) ? trim((string) $item['id']) : '';

            if ($video_id !== '') {
                $videos[$video_id] = $item;
            }
        }

        return $videos;
    }

    private function normalize_video(string $video_id, array $video, array $playlist_item): array
    {
        $snippet = isset($video['snippet']) && is_array($video['snippet']) ? $video['snippet'] : [];
        $playlist_snippet = isset($playlist_item['snippet']) && is_array($playlist_item['snippet']) ? $playlist_item['snippet'] : [];
        $live_details = isset($video['liveStreamingDetails']) && is_array($video['liveStreamingDetails']) ? $video['liveStreamingDetails'] : [];
        $status = isset($video['status']) && is_array($video['status']) ? $video['status'] : [];
        $content_details = isset($video['contentDetails']) && is_array($video['contentDetails']) ? $video['contentDetails'] : [];

        $title = isset($snippet['title']) ? (string) $snippet['title'] : (string) ($playlist_snippet['title'] ?? '');
        $description = isset($snippet['description']) ? (string) $snippet['description'] : '';
        $published_at = isset($snippet['publishedAt']) ? (string) $snippet['publishedAt'] : (string) ($playlist_item['contentDetails']['videoPublishedAt'] ?? '');
        $live_broadcast_content = isset($snippet['liveBroadcastContent'])
            ? (string) $snippet['liveBroadcastContent']
            : (string) ($playlist_snippet['liveBroadcastContent'] ?? 'none');

        return [
            'video_id' => $video_id,
            'title' => $title,
            'description' => $description,
            'published_at' => $published_at,
            'privacy_status' => isset($status['privacyStatus']) ? (string) $status['privacyStatus'] : '',
            'live_broadcast_content' => $live_broadcast_content,
            'scheduled_start_time' => isset($live_details['scheduledStartTime']) ? (string) $live_details['scheduledStartTime'] : '',
            'actual_start_time' => isset($live_details['actualStartTime']) ? (string) $live_details['actualStartTime'] : '',
            'actual_end_time' => isset($live_details['actualEndTime']) ? (string) $live_details['actualEndTime'] : '',
            'duration' => isset($content_details['duration']) ? (string) $content_details['duration'] : '',
            'youtube_url' => 'https://www.youtube.com/watch?v=' . rawurlencode($video_id),
            'is_livestream' => $live_broadcast_content !== 'none' || $live_details !== [],
        ];
    }

    private function request(string $endpoint, array $query_args)
    {
        if ($this->api_key === '' || $this->channel_id === '') {
            return new WP_Error(
                'church_core_youtube_missing_settings',
                __('YouTube sync is not configured yet. Add the API key and channel ID first.', 'church-core')
            );
        }

        $url = add_query_arg(
            array_merge(
                $query_args,
                [
                    'key' => $this->api_key,
                ]
            ),
            self::API_BASE . ltrim($endpoint, '/')
        );

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'Church Core YouTube Sync; ' . home_url('/'),
            ],
        ]);

        if (is_wp_error($response)) {
            return new WP_Error(
                'church_core_youtube_request_failed',
                sprintf(
                    __('The request to YouTube failed: %s', 'church-core'),
                    $response->get_error_message()
                )
            );
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (! is_array($data)) {
            return new WP_Error(
                'church_core_youtube_invalid_json',
                __('YouTube returned an invalid JSON response.', 'church-core')
            );
        }

        if ($status_code < 200 || $status_code >= 300) {
            $message = isset($data['error']['message']) ? (string) $data['error']['message'] : __('Unknown YouTube API error.', 'church-core');

            return new WP_Error(
                'church_core_youtube_api_error',
                sprintf(
                    __('YouTube API error (%1$d): %2$s', 'church-core'),
                    $status_code,
                    $message
                ),
                [
                    'status_code' => $status_code,
                    'response' => $data,
                ]
            );
        }

        return $data;
    }
}
