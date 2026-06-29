<?php
if (! defined('ABSPATH')) {
    exit;
}

function church_theme_get_instagram_profile_url(): string
{
    $profile_url = trim(church_theme_get_mod('instagram_profile_url'));

    if ($profile_url !== '') {
        return $profile_url;
    }

    $username = ltrim(trim(church_theme_get_mod('instagram_username')), '@');

    if ($username === '') {
        return '';
    }

    return 'https://www.instagram.com/' . rawurlencode($username) . '/';
}

function church_theme_get_instagram_media_endpoint(string $account_id): string
{
    return sprintf('https://graph.instagram.com/v25.0/%s/media', rawurlencode($account_id));
}

function church_theme_normalize_instagram_media_item(array $item): ?array
{
    $media_type = sanitize_text_field((string) ($item['media_type'] ?? ''));
    $image_url = '';

    if ($media_type === 'VIDEO') {
        $image_url = (string) ($item['thumbnail_url'] ?? $item['media_url'] ?? '');
    } elseif ($media_type === 'CAROUSEL_ALBUM') {
        $children = $item['children']['data'][0] ?? [];
        $image_url = (string) ($item['thumbnail_url'] ?? $children['media_url'] ?? $children['thumbnail_url'] ?? $item['media_url'] ?? '');
    } else {
        $image_url = (string) ($item['media_url'] ?? $item['thumbnail_url'] ?? '');
    }

    $permalink = (string) ($item['permalink'] ?? '');

    if ($image_url === '' || $permalink === '') {
        return null;
    }

    return [
        'id' => sanitize_text_field((string) ($item['id'] ?? '')),
        'caption' => sanitize_textarea_field(wp_strip_all_tags((string) ($item['caption'] ?? ''))),
        'image_url' => esc_url_raw($image_url),
        'permalink' => esc_url_raw($permalink),
        'timestamp' => sanitize_text_field((string) ($item['timestamp'] ?? '')),
        'media_type' => $media_type,
    ];
}

function church_theme_get_instagram_feed(int $limit = 9): array
{
    $account_id = church_theme_sanitize_instagram_account_id(church_theme_get_mod('instagram_account_id'));
    $access_token = trim(church_theme_get_mod('instagram_access_token'));
    $fallback = [
        'configured' => false,
        'items' => [],
        'error' => false,
    ];

    if ($account_id === '' || $access_token === '') {
        return $fallback;
    }

    $transient_key = 'church_theme_ig_' . md5($account_id . '|' . $access_token . '|' . $limit);
    $cached = get_transient($transient_key);

    if (is_array($cached)) {
        return $cached;
    }

    $endpoint = add_query_arg([
        'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,children{media_type,media_url,thumbnail_url}',
        'limit' => max(1, $limit),
        'access_token' => $access_token,
    ], church_theme_get_instagram_media_endpoint($account_id));
    $response = wp_safe_remote_get($endpoint, [
        'timeout' => 15,
    ]);
    $response_code = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);

    if (is_wp_error($response) || $response_code < 200 || $response_code >= 300) {
        $result = [
            'configured' => true,
            'items' => [],
            'error' => true,
        ];
        set_transient($transient_key, $result, 5 * MINUTE_IN_SECONDS);

        return $result;
    }

    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    $items = [];

    if (! is_array($body['data'] ?? null)) {
        $result = [
            'configured' => true,
            'items' => [],
            'error' => true,
        ];
        set_transient($transient_key, $result, 5 * MINUTE_IN_SECONDS);

        return $result;
    }

    if (is_array($body['data'])) {
        foreach ($body['data'] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $normalized = church_theme_normalize_instagram_media_item($item);

            if (is_array($normalized)) {
                $items[] = $normalized;
            }
        }
    }

    $result = [
        'configured' => true,
        'items' => $items,
        'error' => false,
    ];
    set_transient($transient_key, $result, 15 * MINUTE_IN_SECONDS);

    return $result;
}
