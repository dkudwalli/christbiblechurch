<?php
if (! defined('ABSPATH')) {
    exit;
}
get_header();

$hero_url = church_theme_resolve_url(church_theme_get_mod('hero_primary_url'));
$contact_url = church_theme_get_page_url('contact-us');
$service_times = church_theme_split_lines(church_theme_get_mod('service_times'));
$worship_location = church_theme_split_lines(church_theme_get_mod('worship_location'));
$directions_url = church_theme_get_map_directions_url();
$latest_sermon = church_theme_get_latest_sermon_query();
$upcoming_events = church_theme_get_event_query(true, 3);
$hero_community_image = church_theme_get_hero_community_image();
?>
<section class="hero home-hero">
    <div class="wrap">
        <div class="hero__content">
            <h1><?php echo esc_html(church_theme_get_mod('hero_title')); ?></h1>
            <p class="hero__summary"><?php echo esc_html(church_theme_get_mod('welcome_summary')); ?></p>
        </div>
        <article class="card card--accent home-visit">
            <div>
                <p class="card__label"><?php esc_html_e('Join us this Sunday', 'church-theme'); ?></p>
                <?php if ($service_times !== []) : ?>
                    <p class="home-visit__time"><?php echo church_theme_icon('clock'); ?><?php echo esc_html($service_times[0]); ?></p>
                <?php endif; ?>
                <?php if ($worship_location !== []) : ?>
                    <p class="home-visit__venue"><?php echo church_theme_icon('location'); ?><?php echo esc_html($worship_location[0]); ?></p>
                <?php endif; ?>
            </div>
            <?php if ($directions_url !== '') : ?>
                <a class="button" href="<?php echo esc_url($directions_url); ?>" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Get Directions', 'church-theme'); ?>
                </a>
            <?php endif; ?>
        </article>
        <nav class="home-shortcuts" aria-label="<?php esc_attr_e('Start here', 'church-theme'); ?>">
            <a href="<?php echo esc_url(church_theme_get_page_url('worship')); ?>"><?php esc_html_e('Visit', 'church-theme'); ?></a>
            <a href="<?php echo esc_url(church_theme_get_sermon_archive_url()); ?>"><?php esc_html_e('Sermons', 'church-theme'); ?></a>
            <a href="<?php echo esc_url(church_theme_get_event_archive_url()); ?>"><?php esc_html_e('Events', 'church-theme'); ?></a>
        </nav>
        <?php if ($hero_url !== '') : ?>
            <a class="text-link home-hero__custom-cta" href="<?php echo esc_url($hero_url); ?>"><?php echo esc_html(church_theme_get_mod('hero_primary_label')); ?></a>
        <?php endif; ?>
        <?php if ($hero_community_image !== null) : ?>
            <figure class="hero__media">
                <?php echo church_theme_render_static_image($hero_community_image, [
                    'loading' => 'eager',
                    'fetchpriority' => 'high',
                    'decoding' => 'sync',
                    'sizes' => '(max-width: 720px) calc(100vw - 32px), (max-width: 1152px) calc((100vw - 64px) / 2), 544px',
                ]); ?>
            </figure>
        <?php endif; ?>
    </div>
</section>

<?php get_template_part('template-parts/hero-banner'); ?>

<section class="section home-sermon">
    <div class="wrap">
        <div class="section-heading reveal">
            <p class="eyebrow"><?php esc_html_e('Sermons', 'church-theme'); ?></p>
            <h2><?php echo esc_html(church_theme_get_mod('latest_sermon_heading')); ?></h2>
        </div>
        <?php if ($latest_sermon->have_posts()) : ?>
            <?php $latest_sermon->the_post(); ?>
            <?php $series_term = church_theme_get_sermon_primary_term(get_the_ID(), 'series'); ?>
            <article class="card sermon-feature reveal">
                <a class="sermon-feature__preview" href="<?php echo esc_url(church_theme_get_sermon_url(get_the_ID())); ?>" aria-label="<?php echo esc_attr(sprintf(__('Open sermon: %s', 'church-theme'), get_the_title())); ?>">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php echo get_the_post_thumbnail(get_the_ID(), 'large', ['loading' => 'lazy', 'sizes' => '(max-width: 720px) 100vw, 45vw']); ?>
                    <?php else : ?>
                        <span class="sermon-feature__preview-fallback">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M8 5v14l11-7z"></path>
                            </svg>
                        </span>
                    <?php endif; ?>
                    <span><?php esc_html_e('Watch or listen', 'church-theme'); ?></span>
                </a>
                <div class="sermon-feature__content">
                    <h3><a href="<?php echo esc_url(church_theme_get_sermon_url(get_the_ID())); ?>"><?php the_title(); ?></a></h3>
                    <p class="sermon-meta">
                        <span><?php echo esc_html(church_theme_get_sermon_date(get_the_ID())); ?></span>
                        <?php if ($series_term) : ?>
                            <span><a href="<?php echo esc_url(church_theme_get_sermon_term_url($series_term)); ?>"><?php echo esc_html($series_term->name); ?></a></span>
                        <?php endif; ?>
                        <?php $scripture = (string) get_post_meta(get_the_ID(), 'scripture_reference', true); ?>
                        <?php if ($scripture !== '') : ?>
                            <span><?php echo esc_html($scripture); ?></span>
                        <?php endif; ?>
                    </p>
                    <p><?php echo esc_html(church_theme_get_post_preview(get_the_ID(), 24)); ?></p>
                    <a class="text-link" href="<?php echo esc_url(church_theme_get_sermon_archive_url()); ?>"><?php esc_html_e('Browse all sermons', 'church-theme'); ?></a>
                </div>
            </article>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <article class="card reveal">
                <h3><?php esc_html_e('We’re preparing our sermon archive.', 'church-theme'); ?></h3>
            </article>
        <?php endif; ?>
    </div>
</section>

<section class="section section--muted home-events">
    <div class="wrap">
        <div class="section-heading reveal">
            <p class="eyebrow"><?php esc_html_e('Community', 'church-theme'); ?></p>
            <h2><?php esc_html_e('Upcoming opportunities to gather.', 'church-theme'); ?></h2>
        </div>
        <?php if (! church_theme_render_post_grid($upcoming_events, ['template-parts/event', 'card'], 'event-grid reveal-stagger')) : ?>
            <article class="card reveal">
                <h3><?php esc_html_e('No upcoming events right now.', 'church-theme'); ?></h3>
            </article>
        <?php endif; ?>
        <a class="text-link" href="<?php echo esc_url(church_theme_get_event_archive_url()); ?>"><?php esc_html_e('View all events', 'church-theme'); ?></a>
    </div>
</section>

<section class="section home-mission">
    <div class="wrap">
        <div class="section-heading reveal">
            <p class="eyebrow"><?php esc_html_e('Our Mission, Vision and Core Values', 'church-theme'); ?></p>
            <h2><?php esc_html_e('What shapes our life together.', 'church-theme'); ?></h2>
        </div>
        <div class="summary-grid reveal-stagger">
            <article class="card summary-card">
                <p class="card__label"><?php esc_html_e('Mission', 'church-theme'); ?></p>
                <p><?php echo esc_html(church_theme_get_mod('mission_statement')); ?></p>
            </article>
            <article class="card summary-card">
                <p class="card__label"><?php esc_html_e('Vision', 'church-theme'); ?></p>
                <p><?php echo esc_html(church_theme_get_mod('vision_statement')); ?></p>
            </article>
            <article class="card summary-card">
                <p class="card__label"><?php esc_html_e('Core Values', 'church-theme'); ?></p>
                <p><?php echo esc_html(church_theme_get_mod('core_values_summary')); ?></p>
            </article>
        </div>
    </div>
</section>

<section class="section section--muted">
    <div class="wrap callout reveal">
        <div>
            <p class="eyebrow"><?php esc_html_e('Connect', 'church-theme'); ?></p>
            <h2><?php esc_html_e('Questions before Sunday?', 'church-theme'); ?></h2>
        </div>
        <div class="callout__actions">
            <a class="button" href="<?php echo esc_url($contact_url); ?>"><?php esc_html_e('Contact Us', 'church-theme'); ?></a>
            <a class="text-link" href="<?php echo esc_url(church_theme_get_page_url('worship')); ?>"><?php esc_html_e('Explore Worship', 'church-theme'); ?></a>
        </div>
    </div>
</section>
<?php get_footer();
