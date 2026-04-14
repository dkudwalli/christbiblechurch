<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    $post_id = get_the_ID();
    $event_location = church_theme_get_event_location($post_id);
    $event_notes = get_the_content();
    $has_notes = trim(wp_strip_all_tags($event_notes)) !== '';
    ?>
    <section class="page-hero">
        <div class="wrap">
            <p class="eyebrow"><?php esc_html_e('Event', 'church-theme'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="page-hero__summary"><?php echo esc_html(church_theme_get_event_datetime($post_id)); ?></p>
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            <div class="event-detail">
                <section class="event-meta-grid" aria-label="<?php esc_attr_e('Event details', 'church-theme'); ?>">
                    <article class="card event-meta-card">
                        <p class="eyebrow"><?php esc_html_e('Date & Time', 'church-theme'); ?></p>
                        <h2><?php echo esc_html(church_theme_get_event_datetime($post_id)); ?></h2>
                    </article>

                    <article class="card event-meta-card">
                        <p class="eyebrow"><?php esc_html_e('Location', 'church-theme'); ?></p>
                        <h2><?php echo esc_html($event_location !== '' ? $event_location : __('Location to be announced', 'church-theme')); ?></h2>
                    </article>
                </section>

                <article class="card event-summary">
                    <p class="eyebrow"><?php esc_html_e('Notes', 'church-theme'); ?></p>
                    <?php if ($has_notes) : ?>
                        <div class="prose prose--wide">
                            <?php echo apply_filters('the_content', $event_notes); ?>
                        </div>
                    <?php else : ?>
                        <p><?php esc_html_e('More details for this event will be added soon.', 'church-theme'); ?></p>
                    <?php endif; ?>
                </article>

                <article class="card event-actions">
                    <div>
                        <p class="eyebrow"><?php esc_html_e('More Events', 'church-theme'); ?></p>
                        <h2><?php esc_html_e('See everything on the events page.', 'church-theme'); ?></h2>
                        <p><?php esc_html_e('Browse all upcoming and past events from Crossroad South Church.', 'church-theme'); ?></p>
                    </div>

                    <a class="button button--secondary" href="<?php echo esc_url(church_theme_get_event_archive_url()); ?>">
                        <?php esc_html_e('Back to all events', 'church-theme'); ?>
                    </a>
                </article>
            </div>
        </div>
    </section>
<?php endwhile; ?>
<?php
get_footer();
