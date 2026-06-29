<?php
/**
 * Regression test for the per-slug route-shim failure notice (audit silentfail-3):
 * the shared notice option must hold one entry PER slug so that clearing/healing one
 * item's notice never erases another item's still-pending failure notice.
 *
 * Exercises the shared trait directly via a tiny probe class. Runs via WP-CLI
 * `wp eval-file` (WordPress loaded so the trait + options API are available).
 */

if (! defined('ABSPATH')) {
    exit(1);
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

if (! trait_exists('Church_Core_Route_Shim_Writer')) {
    church_test_fail('Route shim writer trait is not loaded.');
}

final class Church_Test_Shim_Notice_Probe
{
    use Church_Core_Route_Shim_Writer;

    public const ROUTE_ROOT = 'church-test-shim-probe';
    public const ROUTE_SHIM_NOTICE_OPTION = 'church_test_shim_notice';

    protected static function get_route_shim_contents(): string
    {
        return "<?php\n";
    }

    public static function probe_store(string $slug, string $reason): void
    {
        self::store_route_shim_notice($slug, '/tmp/' . $slug, $reason);
    }

    public static function probe_clear(string $slug): void
    {
        self::clear_route_shim_notice($slug);
    }

    public static function probe_clear_all(): void
    {
        self::clear_route_shim_notice();
    }
}

$opt = Church_Test_Shim_Notice_Probe::ROUTE_SHIM_NOTICE_OPTION;
delete_option($opt);

// Two distinct slugs fail → two independent notices coexist.
Church_Test_Shim_Notice_Probe::probe_store('alpha', 'reason alpha');
Church_Test_Shim_Notice_Probe::probe_store('beta', 'reason beta');

$map = get_option($opt);
church_test_assert(is_array($map) && count($map) === 2, 'Two failing slugs should produce two keyed notices.');
church_test_assert(isset($map['alpha'], $map['beta']), 'Both slugs should be present in the notice map.');
church_test_assert(($map['beta']['reason'] ?? '') === 'reason beta', 'Each notice should retain its own reason.');

// Clearing/healing one slug must NOT erase the other's notice (the core bug fix).
Church_Test_Shim_Notice_Probe::probe_clear('alpha');
$map = get_option($opt);
church_test_assert(is_array($map) && count($map) === 1, 'Clearing one slug should leave the other notice intact.');
church_test_assert(! isset($map['alpha']) && isset($map['beta']), 'Only the cleared slug should be removed.');

// Clearing the last entry deletes the option entirely.
Church_Test_Shim_Notice_Probe::probe_clear('beta');
church_test_assert(get_option($opt, '__missing__') === '__missing__', 'Clearing the last slug should delete the option.');

// clear-all (post-reconcile success) wipes everything.
Church_Test_Shim_Notice_Probe::probe_store('alpha', 'reason alpha');
Church_Test_Shim_Notice_Probe::probe_store('beta', 'reason beta');
Church_Test_Shim_Notice_Probe::probe_clear_all();
church_test_assert(get_option($opt, '__missing__') === '__missing__', 'clear-all should remove every notice.');

delete_option($opt);

fwrite(STDOUT, "Route shim notice keying checks passed.\n");
