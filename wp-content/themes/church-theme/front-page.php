<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

$hero_url = church_theme_resolve_url(church_theme_get_mod('hero_primary_url'));
$service_times = church_theme_split_lines(church_theme_get_mod('service_times'));
$worship_location = church_theme_split_lines(church_theme_get_mod('worship_location'));
$latest_sermon = new WP_Query([
    'post_type' => 'sermon',
    'posts_per_page' => 1,
    'meta_key' => 'sermon_date',
    'orderby' => 'meta_value',
    'order' => 'DESC',
]);
$upcoming_events = church_theme_get_event_query(true, 3);
?>
<section class="hero">
    <div class="wrap hero__grid">
        <div class="hero__content">
            <?php if (church_theme_get_mod('hero_eyebrow') !== '') : ?>
                <p class="eyebrow"><?php echo esc_html(church_theme_get_mod('hero_eyebrow')); ?></p>
            <?php endif; ?>
            <h1><?php echo esc_html(church_theme_get_mod('hero_title')); ?></h1>
            <p class="hero__summary"><?php echo esc_html(church_theme_get_mod('welcome_summary')); ?></p>

            <div class="hero__actions">
                <?php if ($hero_url !== '') : ?>
                    <a class="button" href="<?php echo esc_url($hero_url); ?>">
                        <?php echo esc_html(church_theme_get_mod('hero_primary_label')); ?>
                    </a>
                <?php endif; ?>

                <a class="text-link" href="<?php echo esc_url(church_theme_get_page_url('about-us')); ?>">
                    <?php esc_html_e('Learn more about Crossroads', 'church-theme'); ?>
                </a>
            </div>
        </div>

        <aside class="card card--accent hero__card">
            <p class="card__label"><?php esc_html_e('Gather With Us', 'church-theme'); ?></p>
            <?php if ($worship_location !== []) : ?>
                <h2><?php echo esc_html($worship_location[0]); ?></h2>
                <p class="hero__card-copy"><?php echo esc_html(implode(', ', $worship_location)); ?></p>
            <?php endif; ?>

            <?php if ($service_times !== []) : ?>
                <ul class="stack-list">
                    <?php foreach ($service_times as $time) : ?>
                        <li><?php echo esc_html($time); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </aside>
    </div>
</section>

<section class="section section--muted">
    <div class="wrap">
        <div class="section-heading">
            <p class="eyebrow"><?php esc_html_e('Welcome', 'church-theme'); ?></p>
            <h2><?php esc_html_e('The priorities shaping Crossroad South Church.', 'church-theme'); ?></h2>
        </div>

        <div class="summary-grid">
            <article class="card summary-card">
                <p class="card__label"><?php esc_html_e('Mission', 'church-theme'); ?></p>
                <h3><?php esc_html_e('Exalt, Edify, Evangelize', 'church-theme'); ?></h3>
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
                <p><?php echo esc_html(church_theme_get_mod('core_values_summary')); ?></p>
            </article>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="section-heading">
            <p class="eyebrow"><?php esc_html_e('Teaching', 'church-theme'); ?></p>
            <h2><?php echo esc_html(church_theme_get_mod('latest_sermon_heading')); ?></h2>
        </div>

        <?php if ($latest_sermon->have_posts()) : ?>
            <?php $latest_sermon->the_post(); ?>
            <?php $series_term = church_theme_get_sermon_primary_term(get_the_ID(), 'series'); ?>
            <article class="card sermon-feature">
                <div>
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
                    <p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 28)); ?></p>
                </div>

                <div class="sermon-feature__actions">
                    <a class="button button--secondary" href="<?php echo esc_url(church_theme_get_sermon_url(get_the_ID())); ?>">
                        <?php esc_html_e('Watch or Listen', 'church-theme'); ?>
                    </a>
                    <a class="text-link" href="<?php echo esc_url(church_theme_get_sermon_archive_url()); ?>">
                        <?php esc_html_e('Browse all sermons', 'church-theme'); ?>
                    </a>
                </div>
            </article>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <article class="card">
                <h3><?php esc_html_e('Sermon migration is next.', 'church-theme'); ?></h3>
                <p><?php esc_html_e('The Crossroads page structure is in place. The historic sermon archive will be imported in the next phase.', 'church-theme'); ?></p>
            </article>
        <?php endif; ?>
    </div>
</section>

<section class="section section--muted">
    <div class="wrap">
        <div class="section-heading">
            <p class="eyebrow"><?php esc_html_e('Events', 'church-theme'); ?></p>
            <h2><?php esc_html_e('Upcoming opportunities to gather.', 'church-theme'); ?></h2>
            <p class="page-hero__summary"><?php esc_html_e('See the next few church events at a glance, then head to the full Events page for more details and past gatherings.', 'church-theme'); ?></p>
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
                <h3><?php esc_html_e('Upcoming events will appear here soon.', 'church-theme'); ?></h3>
                <p><?php esc_html_e('As new meetings, fellowships, and special gatherings are added, the next three upcoming events will show on this page.', 'church-theme'); ?></p>
            </article>
        <?php endif; ?>

        <div class="callout__actions">
            <a class="button" href="<?php echo esc_url(church_theme_get_event_archive_url()); ?>">
                <?php esc_html_e('View All Events', 'church-theme'); ?>
            </a>
        </div>
    </div>
</section>

<section class="section section--muted">
    <div class="wrap callout">
        <div>
            <p class="eyebrow"><?php esc_html_e('Visit', 'church-theme'); ?></p>
            <h2><?php esc_html_e('Questions before Sunday?', 'church-theme'); ?></h2>
            <p><?php echo esc_html(church_theme_get_mod('footer_invite')); ?></p>
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
