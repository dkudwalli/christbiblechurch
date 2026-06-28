<?php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Maintainer WP-CLI command that regenerates the COMMITTED route-shim tree
 * (sermons listing + pagination + per-sermon, and series/speaker listing + term +
 * pagination) deterministically from the database.
 *
 * Workflow: run against the production content (or a prod DB dump) in a checkout
 * where the WordPress root is the repository, then commit the result. The runtime
 * generator (Church_Core_Sermon_Route_Shims) additionally fills in per-sermon
 * shims for sermons created by the weekly cron between regenerations; this command
 * is how the low-churn series/speaker shims and pagination stay in sync.
 *
 * Usage:
 *   wp church-core regenerate-shims            # write/update the shim tree under ABSPATH
 *   wp church-core regenerate-shims --check    # report drift, write nothing, non-zero on diff
 *   wp church-core regenerate-shims --root=/path/to/repo   # target a specific tree
 */
final class Church_Core_Shim_CLI
{
    private const PER_PAGE = 9;

    /**
     * @param array<int,string>     $args
     * @param array<string,string>  $assoc_args
     */
    public static function regenerate(array $args, array $assoc_args): void
    {
        $check = isset($assoc_args['check']);
        $root = isset($assoc_args['root'])
            ? rtrim((string) $assoc_args['root'], '/')
            : untrailingslashit(ABSPATH);

        $expected = self::build_expected_map();
        $missing = [];
        $mismatch = [];
        $written = 0;

        foreach ($expected as $relative => $contents) {
            $absolute = $root . '/' . $relative;
            $existing = is_readable($absolute) ? file_get_contents($absolute) : false;

            if ($existing === $contents) {
                continue;
            }

            if ($existing === false) {
                $missing[] = $relative;
            } else {
                $mismatch[] = $relative;
            }

            if (! $check) {
                $dir = dirname($absolute);

                if (! is_dir($dir) && ! wp_mkdir_p($dir)) {
                    WP_CLI::error("Could not create directory: {$dir}");
                }

                if (@file_put_contents($absolute, $contents, LOCK_EX) === false) {
                    WP_CLI::error("Could not write shim: {$absolute}");
                }

                $written++;
            }
        }

        if ($check) {
            $drift = count($missing) + count($mismatch);

            foreach ($missing as $relative) {
                WP_CLI::log("missing:  {$relative}");
            }

            foreach ($mismatch as $relative) {
                WP_CLI::log("mismatch: {$relative}");
            }

            if ($drift > 0) {
                WP_CLI::error(sprintf('%d shim(s) drift from the database. Run without --check to regenerate.', $drift));
            }

            WP_CLI::success(sprintf('All %d expected shims match.', count($expected)));

            return;
        }

        WP_CLI::success(sprintf('Regenerated %d shim(s) (of %d expected) under %s.', $written, count($expected), $root));
    }

    /**
     * @return array<string,string> relative path => exact file contents
     */
    private static function build_expected_map(): array
    {
        $expected = [];

        // Sermon archive: listing, pagination, and one shim per published sermon.
        $sermon_ids = get_posts([
            'post_type' => 'sermon',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'suppress_filters' => true,
        ]);

        $expected['sermons/index.php'] = self::page_shim(1);

        for ($page = 2, $pages = self::page_count(count($sermon_ids)); $page <= $pages; $page++) {
            $expected["sermons/page/{$page}/index.php"] = self::page_shim(3);
        }

        foreach ($sermon_ids as $sermon_id) {
            $slug = get_post_field('post_name', (int) $sermon_id);

            if ($slug !== '' && ! in_array($slug, ['page', 'index'], true)) {
                $expected["sermons/{$slug}/index.php"] = self::page_shim(2);
            }
        }

        // Taxonomy archives: listing + per-term + per-term pagination.
        foreach (['series', 'speaker'] as $taxonomy) {
            $expected["{$taxonomy}/index.php"] = self::page_shim(1);

            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ]);

            if (is_wp_error($terms)) {
                continue;
            }

            foreach ($terms as $term) {
                $count = self::published_sermon_count_for_term($taxonomy, (int) $term->term_id);

                if ($count < 1) {
                    continue; // committed tree only carries non-empty terms
                }

                $slug = (string) $term->slug;
                $expected["{$taxonomy}/{$slug}/index.php"] = self::taxonomy_shim(2, $taxonomy, $slug);

                for ($page = 2, $pages = self::page_count($count); $page <= $pages; $page++) {
                    $expected["{$taxonomy}/{$slug}/page/{$page}/index.php"] = self::taxonomy_shim(4, $taxonomy, $slug, $page);
                }
            }
        }

        return $expected;
    }

    private static function published_sermon_count_for_term(string $taxonomy, int $term_id): int
    {
        $query = new WP_Query([
            'post_type' => 'sermon',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'tax_query' => [[
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => $term_id,
            ]],
        ]);

        return (int) $query->found_posts;
    }

    private static function page_count(int $total): int
    {
        return max(1, (int) ceil($total / self::PER_PAGE));
    }

    private static function dirname_chain(int $depth): string
    {
        return str_repeat('dirname(', $depth) . '__DIR__' . str_repeat(')', $depth);
    }

    private static function page_shim(int $depth): string
    {
        return "<?php\n\nrequire " . self::dirname_chain($depth) . " . '/index.php';\n";
    }

    private static function taxonomy_shim(int $depth, string $taxonomy, string $slug, ?int $page = null): string
    {
        $page_arg = $page !== null ? ", {$page}" : '';

        return "<?php\n\nrequire " . self::dirname_chain($depth) . " . '/taxonomy-route-shim.php';\n\n"
            . "church_route_shim_boot_taxonomy('{$taxonomy}', '{$slug}'{$page_arg});\n";
    }
}
