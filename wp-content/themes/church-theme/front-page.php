<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

$hero_url = church_theme_resolve_url(church_theme_get_mod('hero_primary_url'));
$service_times = church_theme_split_lines(church_theme_get_mod('service_times'));
$worship_location = church_theme_split_lines(church_theme_get_mod('worship_location'));
$latest_sermon = church_theme_get_latest_sermon_query();
$recent_sermons = church_theme_get_latest_sermon_query(3, 1);
$upcoming_events = church_theme_get_event_query(true, 3);
?>
<?php get_template_part('template-parts/hero-banner'); ?>
<section class="hero">
    <div class="wrap hero__grid">
        <div class="hero__content">
            <h1><?php echo esc_html(church_theme_get_mod('hero_title')); ?></h1>
            <p class="hero__summary"><?php echo esc_html(church_theme_get_mod('welcome_summary')); ?></p>

            <div class="hero__actions">
                <?php if ($hero_url !== '') : ?>
                    <a class="button" href="<?php echo esc_url($hero_url); ?>">
                        <?php echo esc_html(church_theme_get_mod('hero_primary_label')); ?>
                    </a>
                <?php endif; ?>

                <a class="text-link" href="<?php echo esc_url(church_theme_get_page_url('about-us')); ?>">
                    <?php esc_html_e('Learn about Crossroad South', 'church-theme'); ?>
                </a>
            </div>

            <?php if ($service_times !== [] || $worship_location !== []) : ?>
                <p class="hero__meta">
                    <?php if ($service_times !== []) : ?>
                        <span class="hero__meta-item"><?php echo church_theme_icon('clock'); ?><?php echo esc_html($service_times[0]); ?></span>
                    <?php endif; ?>
                    <?php if ($worship_location !== []) : ?>
                        <span class="hero__meta-item"><?php echo church_theme_icon('location'); ?><?php echo esc_html($worship_location[0]); ?></span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="hero__aside">
            <?php
            $hero_community_image = church_theme_get_hero_community_image();
            ?>
            <?php if ($hero_community_image !== null) : ?>
                <figure class="hero__media">
                    <?php
                    echo church_theme_render_static_image($hero_community_image, [
                        'loading' => 'eager',
                        'fetchpriority' => 'high',
                        'decoding' => 'sync',
                        'sizes' => '(max-width: 960px) 100vw, 48vw',
                    ]);
                    ?>
                </figure>
            <?php endif; ?>

            <div class="card card--accent hero__card">
                <p class="card__label"><?php esc_html_e('Gather With Us', 'church-theme'); ?></p>
                <?php if ($worship_location !== []) : ?>
                    <h2><?php echo esc_html($worship_location[0]); ?></h2>
                    <p class="hero__card-copy">
                        <?php echo church_theme_icon('location'); ?>
                        <?php echo esc_html(implode(', ', $worship_location)); ?>
                    </p>
                <?php endif; ?>

                <?php if ($service_times !== []) : ?>
                    <ul class="stack-list">
                        <?php foreach ($service_times as $time) : ?>
                            <li>
                                <?php echo church_theme_icon('clock'); ?>
                                <?php echo esc_html($time); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section section--muted">
    <div class="wrap">
        <div class="section-heading reveal">
            <p class="eyebrow"><?php esc_html_e('Our Mission, Vision and Core Values', 'church-theme'); ?></p>
            <h2><?php esc_html_e('The priorities shaping Crossroad South Church.', 'church-theme'); ?></h2>
        </div>

        <div class="summary-grid reveal-stagger">
            <article class="card summary-card reveal">
                <p class="card__label"><?php esc_html_e('Mission', 'church-theme'); ?></p>
                <h3><span class="triple-e"><?php esc_html_e('Exalt', 'church-theme'); ?></span>, <span class="triple-e"><?php esc_html_e('Edify', 'church-theme'); ?></span>, <span class="triple-e"><?php esc_html_e('Evangelize', 'church-theme'); ?></span></h3>
                <p><?php echo esc_html(church_theme_get_mod('mission_statement')); ?></p>
            </article>

            <article class="card summary-card">
                <p class="card__label"><?php esc_html_e('Vision', 'church-theme'); ?></p>
                <h3><?php esc_html_e('Discipleship as a way of life', 'church-theme'); ?></h3>
                <p><?php echo esc_html(church_theme_get_mod('vision_statement')); ?></p>
            </article>

            <article class="card summary-card">
                <p class="card__label"><?php esc_html_e('Core Values', 'church-theme'); ?></p>
                <h3><?php esc_html_e('Gospel-centered and deeply biblical', 'church-theme'); ?></h3>
                <ul class="core-values-list">
                    <li><?php esc_html_e('Gospel-Centered', 'church-theme'); ?></li>
                    <li><?php esc_html_e('Deeply Biblical', 'church-theme'); ?></li>
                    <li><?php esc_html_e('Breaking Down Barriers', 'church-theme'); ?></li>
                    <li><?php esc_html_e('Missional Living', 'church-theme'); ?></li>
                </ul>
            </article>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="section-heading reveal">
            <p class="eyebrow"><?php esc_html_e('Sermons', 'church-theme'); ?></p>
            <h2><?php echo esc_html(church_theme_get_mod('latest_sermon_heading')); ?></h2>
        </div>

        <?php if ($latest_sermon->have_posts()) : ?>
            <?php $latest_sermon->the_post(); ?>
            <?php $series_term = church_theme_get_sermon_primary_term(get_the_ID(), 'series'); ?>
            <article class="card sermon-feature reveal">
                <?php $youtube_id = (string) get_post_meta(get_the_ID(), 'youtube_video_id', true); ?>
                <?php if ($youtube_id !== '') : ?>
                    <div class="sermon-feature__media">
                        <iframe 
                            src="<?php echo esc_url('https://www.youtube.com/embed/' . $youtube_id); ?>" 
                            title="<?php esc_attr_e('YouTube video player', 'church-theme'); ?>" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen
                            loading="lazy"
                            style="width: 100%; aspect-ratio: 16/9; border-radius: 8px;">
                        </iframe>
                    </div>
                <?php else : ?>
                    <!-- Fallback if no video ID is present -->
                    <div class="sermon-feature__media" style="background: var(--bg-muted); border-radius: 8px; aspect-ratio: 16/9; display: flex; align-items: center; justify-content: center;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                    </div>
                <?php endif; ?>

                <div class="sermon-feature__content" style="display: flex; flex-direction: column; justify-content: center;">
                    <h3><?php the_title(); ?></h3>
                    <p class="sermon-meta">
                        <span><?php echo esc_html(church_theme_get_sermon_date(get_the_ID())); ?></span>
                        <?php if ($series_term) : ?>
                            <span><a href="<?php echo esc_url(church_theme_get_sermon_term_url($series_term)); ?>"><?php echo esc_html($series_term->name); ?></a></span>
                        <?php endif; ?>
                        <?php if (get_post_meta(get_the_ID(), 'scripture_reference', true)) : ?>
                            <span><?php echo esc_html((string) get_post_meta(get_the_ID(), 'scripture_reference', true)); ?></span>
                        <?php endif; ?>
                    </p>
                    <p><?php echo esc_html(church_theme_get_sermon_excerpt_preview(get_the_ID())); ?></p>

                    <div class="sermon-feature__actions" style="margin-top: 1rem;">
                        <a class="button button--secondary" href="<?php echo esc_url(church_theme_get_sermon_url(get_the_ID())); ?>">
                            <?php esc_html_e('Watch or Listen', 'church-theme'); ?>
                        </a>
                        <a class="text-link" href="<?php echo esc_url(church_theme_get_sermon_archive_url()); ?>">
                            <?php esc_html_e('Browse all sermons', 'church-theme'); ?>
                        </a>
                    </div>
                </div>
            </article>
            <?php wp_reset_postdata(); ?>

            <?php if ($recent_sermons->have_posts()) : ?>
                <p class="eyebrow home-recent-sermons__label reveal"><?php esc_html_e('More sermons', 'church-theme'); ?></p>
                <?php church_theme_render_post_grid($recent_sermons, ['template-parts/sermon', 'card'], 'sermon-grid reveal-stagger home-recent-sermons'); ?>
            <?php endif; ?>
        <?php else : ?>
            <article class="card reveal" style="text-align: center; padding: 4rem 2rem;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem; opacity: 0.3; color: var(--accent);"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                <h3><?php esc_html_e('We\'re preparing our sermon archive.', 'church-theme'); ?></h3>
                <p><?php esc_html_e('Check back soon to watch and listen to recent teachings from Crossroad South Church.', 'church-theme'); ?></p>
            </article>
        <?php endif; ?>
    </div>
</section>

<section class="section section--muted">
    <div class="wrap">
        <div class="section-heading reveal">
            <p class="eyebrow"><?php esc_html_e('Community', 'church-theme'); ?></p>
            <h2><?php esc_html_e('Upcoming opportunities to gather.', 'church-theme'); ?></h2>
            <p class="page-hero__summary"><?php esc_html_e('See the next few church events at a glance, then head to the full Events page for more details and past gatherings.', 'church-theme'); ?></p>
        </div>

        <?php if (! church_theme_render_post_grid($upcoming_events, ['template-parts/event', 'card'], 'event-grid reveal-stagger')) : ?>
            <article class="card reveal" style="text-align: center; padding: 4rem 2rem;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem; opacity: 0.3; color: var(--accent);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <h3><?php esc_html_e('No upcoming events right now.', 'church-theme'); ?></h3>
                <p><?php esc_html_e('As new meetings, fellowships, and special gatherings are scheduled, they will appear here.', 'church-theme'); ?></p>
            </article>
        <?php endif; ?>

        <div class="callout__actions event-archive__actions reveal">
            <a class="button" href="<?php echo esc_url(church_theme_get_event_archive_url()); ?>">
                <?php esc_html_e('View All Events', 'church-theme'); ?>
            </a>
        </div>
    </div>
</section>

<section class="section section--muted">
    <div class="wrap callout reveal">
        <div>
            <p class="eyebrow"><?php esc_html_e('Visit', 'church-theme'); ?></p>
            <h2><?php esc_html_e('Questions before Sunday?', 'church-theme'); ?></h2>
        </div>

        <div class="callout__actions">
            <a class="button" href="<?php echo esc_url(church_theme_get_page_url('contact-us')); ?>">
                <?php esc_html_e('Contact Us', 'church-theme'); ?>
            </a>
            <a class="text-link" href="<?php echo esc_url(church_theme_get_page_url('worship')); ?>">
                <?php esc_html_e('Explore Worship', 'church-theme'); ?>
            </a>
        </div>
    </div>
</section>
<?php
get_footer();
