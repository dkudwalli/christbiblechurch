<?php
/**
 * Unit test for Church_Core_Sermon_Sync_Service::should_import_video() — the gate
 * on every unattended weekly import. If the livestream branch regresses, the cron
 * publishes a scheduled-but-not-yet-preached stream as a public sermon and logs it
 * as a success, so the failure is silent until someone notices the site.
 *
 * Portable: runs under WP-CLI `wp eval-file` (WordPress already loaded) AND
 * standalone via bare `php`. The method touches no WordPress functions, so the
 * class is required directly and the private method reached via reflection.
 */

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (! class_exists('Church_Core_Youtube_Client')) {
    require_once __DIR__ . '/../includes/class-church-core-youtube-client.php';
}

if (! class_exists('Church_Core_Sermon_Sync_Service')) {
    require_once __DIR__ . '/../includes/class-church-core-sermon-sync-service.php';
}

function church_test_fail(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function church_test_assert(bool $condition, string $message): void
{
    if (! $condition) {
        church_test_fail($message);
    }
}

// The constructor needs a Youtube_Client, but should_import_video() never touches
// it — skip construction entirely rather than stubbing an HTTP client.
$service = (new ReflectionClass(Church_Core_Sermon_Sync_Service::class))->newInstanceWithoutConstructor();
$gate = new ReflectionMethod(Church_Core_Sermon_Sync_Service::class, 'should_import_video');
$gate->setAccessible(true);

/**
 * @param array<string, string> $overrides
 */
function church_test_video(array $overrides = []): array
{
    return array_merge([
        'video_id' => 'abc123',
        'title' => 'Mark 8:22-26 Jesus Restores the Broken',
        'published_at' => '2026-08-16T10:00:00Z',
        'privacy_status' => 'public',
        'live_broadcast_content' => 'none',
        'actual_end_time' => '',
    ], $overrides);
}

function church_test_gate(array $overrides, bool $expected, string $label): void
{
    global $service, $gate;

    $actual = (bool) $gate->invoke($service, church_test_video($overrides));

    church_test_assert(
        $actual === $expected,
        sprintf('%s: expected %s but got %s.', $label, $expected ? 'import' : 'skip', $actual ? 'import' : 'skip')
    );
}

// A normal published video imports.
church_test_gate([], true, 'ordinary public video');

// Scheduled / in-progress livestreams must NOT import: they are public and titled
// before the message exists.
church_test_gate(['live_broadcast_content' => 'upcoming'], false, 'scheduled livestream');
church_test_gate(['live_broadcast_content' => 'live'], false, 'in-progress livestream');

// A finished broadcast carries an actualEndTime and imports normally, even if the
// broadcast flag has not reverted to 'none' yet.
church_test_gate(
    ['live_broadcast_content' => 'live', 'actual_end_time' => '2026-08-16T11:30:00Z'],
    true,
    'completed livestream with an end time'
);

// Non-public videos never import.
church_test_gate(['privacy_status' => 'unlisted'], false, 'unlisted video');
church_test_gate(['privacy_status' => 'private'], false, 'private video');

// Placeholder titles YouTube returns for removed entries.
church_test_gate(['title' => 'Private video'], false, 'private-video placeholder');
church_test_gate(['title' => 'Deleted video'], false, 'deleted-video placeholder');
church_test_gate(['title' => 'DELETED VIDEO'], false, 'placeholder is case-insensitive');

// Missing required fields.
church_test_gate(['video_id' => ''], false, 'empty video id');
church_test_gate(['title' => ''], false, 'empty title');
church_test_gate(['published_at' => ''], false, 'empty published date');

fwrite(STDOUT, "Sermon sync import-gate checks passed.\n");
