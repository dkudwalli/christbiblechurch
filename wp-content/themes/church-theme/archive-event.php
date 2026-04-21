<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

$upcoming_events = church_theme_get_event_query(true);
$past_events = church_theme_get_event_query(false);
?>
<section class="page-hero">
    <div class="wrap">
        <h1><?php post_type_archive_title(); ?></h1>
        <p class="page-hero__summary"><?php esc_html_e('Find upcoming church events, online meetings, and recent gatherings from Crossroad South Church.', 'church-theme'); ?></p>
    </div>
</section>

<section class="section">
    <div class="wrap event-section">
        <div class="section-heading">
            <h2><?php esc_html_e('Join us at the next gathering.', 'church-theme'); ?></h2>
        </div>

        <?php if ($upcoming_events->have_posts()) : ?>
            <div class="event-grid">
                <?php while ($upcoming_events->have_posts()) : $upcoming_events->the_post(); ?>
                    <?php get_template_part('template-parts/event', 'card'); ?>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        <?php else : ?>
            <article class="card content-placeholder">
                <h3><?php esc_html_e('No upcoming events are listed right now.', 'church-theme'); ?></h3>
                <p><?php esc_html_e('Check back soon for the next upcoming church gathering.', 'church-theme'); ?></p>
            </article>
        <?php endif; ?>
    </div>
</section>

<section class="section section--muted">
    <div class="wrap event-section">
        <div class="section-heading">
            <h2><?php esc_html_e('Recent gatherings and meetings.', 'church-theme'); ?></h2>
        </div>

        <?php if ($past_events->have_posts()) : ?>
            <div class="event-grid">
                <?php while ($past_events->have_posts()) : $past_events->the_post(); ?>
                    <?php get_template_part('template-parts/event', 'card'); ?>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        <?php else : ?>
            <article class="card content-placeholder">
                <h3><?php esc_html_e('Past events will show here once gatherings have taken place.', 'church-theme'); ?></h3>
                <p><?php esc_html_e('After an event’s start time passes, it moves into this section automatically.', 'church-theme'); ?></p>
            </article>
        <?php endif; ?>
    </div>
</section>
<?php
get_footer();
