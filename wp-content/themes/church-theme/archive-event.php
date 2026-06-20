<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

$upcoming_events = church_theme_get_event_query(true);
$past_events = church_theme_get_event_query(false, 9);

$breadcrumb_items = [
    ['label' => __('Home', 'church-theme'), 'url' => home_url('/')],
    ['label' => __('Events', 'church-theme')],
];
?>
<?php
get_template_part('template-parts/page-hero', null, [
    'title' => post_type_archive_title('', false),
    'breadcrumbs' => $breadcrumb_items,
    'summary' => __('Find upcoming church events, online meetings, and recent gatherings from Crossroad South Church.', 'church-theme'),
]);
?>

<section class="section">
    <div class="wrap event-section">
        <div class="section-heading reveal">
            <p class="eyebrow"><?php esc_html_e('Coming Up', 'church-theme'); ?></p>
            <h2><?php esc_html_e('Join us at the next gathering.', 'church-theme'); ?></h2>
        </div>

        <?php if (! church_theme_render_post_grid($upcoming_events, ['template-parts/event', 'card'], 'event-grid reveal-stagger')) : ?>
            <article class="card content-placeholder reveal">
                <h3><?php esc_html_e('No upcoming events are listed right now.', 'church-theme'); ?></h3>
                <p><?php esc_html_e('Check back soon for the next upcoming church gathering.', 'church-theme'); ?></p>
            </article>
        <?php endif; ?>
    </div>
</section>

<section class="section section--muted">
    <div class="wrap event-section">
        <div class="section-heading reveal">
            <p class="eyebrow"><?php esc_html_e('Looking Back', 'church-theme'); ?></p>
            <h2><?php esc_html_e('Recent gatherings and meetings.', 'church-theme'); ?></h2>
        </div>

        <?php if (! church_theme_render_post_grid($past_events, ['template-parts/event', 'card'], 'event-grid reveal-stagger')) : ?>
            <article class="card content-placeholder reveal">
                <h3><?php esc_html_e('Past events will show here once gatherings have taken place.', 'church-theme'); ?></h3>
                <p><?php esc_html_e('After an event\'s start time passes, it moves into this section automatically.', 'church-theme'); ?></p>
            </article>
        <?php endif; ?>
    </div>

    <?php if ($past_events->max_num_pages > 1) : ?>
        <div class="wrap pagination-wrap">
            <?php
            $current_past_page = max(1, (int) ($_GET['past_page'] ?? 1)); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            echo paginate_links([ // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                'base' => add_query_arg('past_page', '%#%', get_post_type_archive_link('event')),
                'format' => '',
                'current' => $current_past_page,
                'total' => $past_events->max_num_pages,
                'mid_size' => 1,
                'prev_text' => __('Previous', 'church-theme'),
                'next_text' => __('Next', 'church-theme'),
            ]);
            ?>
        </div>
    <?php endif; ?>
</section>
<?php
get_footer();
