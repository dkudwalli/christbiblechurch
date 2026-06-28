<?php
/**
 * Unit test for Church_Core_Scripture_Extractor::from_title() — the most
 * logic-dense, regression-prone code in the plugin (book-alias parsing, verse
 * ranges, cross-chapter spans).
 *
 * Portable: runs under WP-CLI `wp eval-file` (WordPress already loaded) AND
 * standalone via bare `php` (defines ABSPATH + minimal stubs for the only three
 * WP functions from_title() touches, then requires the class directly).
 */

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (! function_exists('get_bloginfo')) {
    function get_bloginfo($show = '')
    {
        return 'UTF-8';
    }
}

if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field($str)
    {
        $str = preg_replace('/[\r\n\t ]+/', ' ', (string) $str);

        return trim((string) $str);
    }
}

if (! function_exists('apply_filters')) {
    function apply_filters($hook_name, $value)
    {
        return $value;
    }
}

if (! class_exists('Church_Core_Scripture_Extractor')) {
    require_once __DIR__ . '/../includes/class-church-core-scripture-extractor.php';
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

function church_test_extract(string $title, string $expected): void
{
    $actual = Church_Core_Scripture_Extractor::from_title($title);

    church_test_assert(
        $actual === $expected,
        sprintf('from_title("%s") expected "%s" but got "%s".', $title, $expected, $actual)
    );
}

// Single verse and a same-chapter range.
church_test_extract('Mark 8:22-26 — The Blind Man of Bethsaida', 'Mark 8:22-26');
church_test_extract('John 3:16', 'John 3:16');

// Reference appearing mid-title (not just at the start).
church_test_extract('The Greatest Verse — John 3:16 explained', 'John 3:16');

// Cross-chapter span preserves the end chapter.
church_test_extract('Genesis 1:1-2:3 In the Beginning', 'Genesis 1:1-2:3');

// Numbered books and abbreviations expand to the canonical book name.
church_test_extract('1 Cor 13:4-7 The Love Chapter', '1 Corinthians 13:4-7');
church_test_extract('Ps 23:1 The Lord is my Shepherd', 'Psalms 23:1');
church_test_extract('Rom 8:28 All Things Work Together', 'Romans 8:28');
church_test_extract('1 Jn 4:8 God is Love', '1 John 4:8');

// Non-matches return empty — including a bare time, which has no book token
// before the H:MM and so must NOT be mistaken for a chapter:verse reference.
church_test_extract('Sunday Worship Service', '');
church_test_extract('Evening Service 6:30pm', '');
church_test_extract('Welcome to Crossroad South', '');

fwrite(STDOUT, "Scripture extractor checks passed.\n");
