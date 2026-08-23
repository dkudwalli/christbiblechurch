<?php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Additive runtime generator for sermon pretty-URL route shims.
 *
 * The weekly YouTube sync (Church_Core_Sermon_Cron) creates sermon posts on
 * production via wp_insert_post, but each sermon's file-based route shim
 * (sermons/<slug>/index.php — the Hostinger routing fallback documented in
 * CLAUDE.md) was historically added by hand, so cron-synced sermons had no shim
 * and 404'd under degraded routing.
 *
 * This closes that gap by creating a missing shim whenever a sermon is published
 * (cron or admin) and reconciling all published sermons once after deploy. It is
 * deliberately CREATE-ONLY: the committed sermons/** tree stays the authoritative
 * baseline, ensure_route_shim() only writes when a shim is missing (byte-for-byte
 * content compare), and nothing here removes or renames shims at runtime — so a
 * redeploy of the committed tree can never fight the runtime writer. Slug-rename /
 * deletion cleanup and completeness are handled by `wp church-core regenerate-shims`
 * and the lint:shims CI guard, not at runtime.
 */
final class Church_Core_Sermon_Route_Shims
{
    use Church_Core_Route_Shim_Writer;

    private const ROUTE_ROOT = 'sermons';
    private const ROUTE_SHIM_VERSION = '1';
    private const ROUTE_SHIM_VERSION_OPTION = 'church_core_sermon_route_shim_version';
    private const ROUTE_SHIM_NOTICE_OPTION = 'church_core_sermon_route_shim_notice';

    /**
     * Slugs that would collide with the committed listing/pagination shims
     * (sermons/index.php, sermons/page/N/index.php); never write a per-sermon
     * directory for these.
     */
    private const RESERVED_SLUGS = ['page', 'index'];

    public static function boot(): void
    {
        add_action('save_post_sermon', [__CLASS__, 'sync_route_shim_on_save'], 20, 2);
        add_action('untrashed_post', [__CLASS__, 'handle_untrashed_post']);
        add_action('init', [__CLASS__, 'maybe_reconcile_route_shims'], 20);
        add_action('admin_notices', [__CLASS__, 'maybe_render_route_shim_notice']);
    }

    public static function sync_route_shim_on_save(int $post_id, WP_Post $post): void
    {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if ($post->post_type !== 'sermon') {
            return;
        }

        self::ensure_route_shim_for_post($post);
    }

    public static function handle_untrashed_post(int $post_id): void
    {
        $post = get_post($post_id);

        if (! $post instanceof WP_Post || $post->post_type !== 'sermon') {
            return;
        }

        self::ensure_route_shim_for_post($post);
    }

    /**
     * One-time, version-gated reconcile that self-heals the full sermon shim tree
     * after a deploy (mirrors Church_Core_Photo_Albums::maybe_reconcile_route_shims).
     * Create-only: it never removes shims, so it cannot conflict with the committed
     * baseline; it merely fills in any missing per-sermon shims.
     */
    public static function maybe_reconcile_route_shims(): void
    {
        if (wp_installing()) {
            return;
        }

        // Runs BEFORE the version gate on purpose, and a cheap in-memory guard keeps
        // it to once per request. Anything that creates a sermons/<slug>/ directory
        // also creates the sermons/ parent — including the per-sermon writer on
        // save_post and the plugin's own tests — so the parent can appear long after
        // the gated reconcile has already run and set its version option. Without a
        // sermons/index.php the standard .htaccess (RewriteCond !-d skips existing
        // directories) hands /sermons/ to Apache, which serves 403. Moving this
        // inside the gate reproduces exactly that.
        static $listing_checked = false;

        if (! $listing_checked) {
            $listing_checked = true;
            self::ensure_listing_shim();
        }

        if (get_option(self::ROUTE_SHIM_VERSION_OPTION) === self::ROUTE_SHIM_VERSION) {
            return;
        }

        $post_ids = get_posts([
            'fields' => 'ids',
            'numberposts' => -1,
            'post_status' => 'publish',
            'post_type' => 'sermon',
            'suppress_filters' => true,
        ]);
        $all_synced = true;

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);

            if (! $post instanceof WP_Post) {
                continue;
            }

            $all_synced = self::ensure_route_shim_for_post($post) && $all_synced;
        }

        if (! $all_synced) {
            return;
        }

        update_option(self::ROUTE_SHIM_VERSION_OPTION, self::ROUTE_SHIM_VERSION, false);
        self::clear_route_shim_notice();
    }

    public static function maybe_render_route_shim_notice(): void
    {
        if (! function_exists('get_current_screen')) {
            return;
        }

        $screen = get_current_screen();

        if (! $screen || $screen->post_type !== 'sermon') {
            return;
        }

        $notices = get_option(self::ROUTE_SHIM_NOTICE_OPTION);

        if (! is_array($notices) || $notices === []) {
            return;
        }

        delete_option(self::ROUTE_SHIM_NOTICE_OPTION);

        // Notices are a map keyed by slug; tolerate the older single-notice shape.
        if (isset($notices['reason']) || isset($notices['path'])) {
            $notices = [$notices];
        }
        ?>
        <div class="notice notice-error">
            <?php foreach ($notices as $notice) : ?>
                <?php if (is_array($notice)) : ?>
                    <p>
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: 1: sermon slug, 2: filesystem path, 3: failure reason. */
                                __('Sermon route fallback could not be created for slug "%1$s". Hostinger may show "No content found" for this sermon until a committed shim is added. Path: %2$s. %3$s', 'church-core'),
                                isset($notice['slug']) && $notice['slug'] !== '' ? (string) $notice['slug'] : '(unknown)',
                                isset($notice['path']) && $notice['path'] !== '' ? (string) $notice['path'] : '(unknown)',
                                isset($notice['reason']) && $notice['reason'] !== '' ? (string) $notice['reason'] : 'Check that WordPress can write to the site root.'
                            )
                        );
                        ?>
                    </p>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private static function ensure_route_shim_for_post(WP_Post $post): bool
    {
        // Additive only: nothing to do for non-published sermons (no removal).
        if ($post->post_status !== 'publish') {
            return true;
        }

        $slug = self::normalize_route_slug($post->post_name);

        if ($slug === '' || in_array($slug, self::RESERVED_SLUGS, true)) {
            return true;
        }

        return self::ensure_route_shim($slug);
    }

    /**
     * Create-if-missing for the sermons/index.php archive listing shim (one
     * dirname() level: sermons/ -> web root). Matches the committed listing shim
     * byte-for-byte so it is never rewritten where the committed tree is deployed.
     */
    private static function ensure_listing_shim(): bool
    {
        $route_root = self::get_route_root_path();

        if (! is_dir($route_root) && ! wp_mkdir_p($route_root)) {
            return false;
        }

        $listing_file = $route_root . '/index.php';
        $expected = self::get_listing_shim_contents();
        $existing = file_exists($listing_file) ? file_get_contents($listing_file) : false;

        if ($existing === $expected) {
            return true;
        }

        return @file_put_contents($listing_file, $expected, LOCK_EX) !== false;
    }

    protected static function get_route_shim_contents(): string
    {
        // Match the committed sermon shim format byte-for-byte (note the blank line
        // after the opening tag) so create-if-missing never rewrites a committed file.
        return "<?php\n\nrequire dirname(dirname(__DIR__)) . '/index.php';\n";
    }

    private static function get_listing_shim_contents(): string
    {
        return "<?php\n\nrequire dirname(__DIR__) . '/index.php';\n";
    }
}
