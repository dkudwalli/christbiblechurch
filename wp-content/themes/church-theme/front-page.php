<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

$hero_url = church_theme_resolve_url(church_theme_get_mod('hero_primary_url'));
$service_times = church_theme_split_lines(church_theme_get_mod('service_times'));
$latest_sermon = new WP_Query([
    'post_type' => 'sermon',
    'posts_per_page' => 1,
    'meta_key' => 'sermon_date',
    'orderby' => 'meta_value',
    'order' => 'DESC',
]);
?>
<section class="hero">
    <div class="wrap hero__grid">
        <div class="hero__content">
            <p class="eyebrow"><?php echo esc_html(church_theme_get_mod('hero_eyebrow')); ?></p>
            <h1><?php echo esc_html(church_theme_get_mod('hero_title')); ?></h1>
            <p class="hero__verse">
                <span class="hero__verse-reference"><?php esc_html_e('2 Corinthians 4:5', 'church-theme'); ?></span>
                <span class="hero__verse-text"><?php esc_html_e('What we proclaim is not ourselves, but JESUS CHRIST as LORD', 'church-theme'); ?></span>
            </p>

            <?php if ($hero_url !== '') : ?>
                <div class="hero__actions">
                    <a class="button" href="<?php echo esc_url($hero_url); ?>">
                        <?php echo esc_html(church_theme_get_mod('hero_primary_label')); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <aside class="card card--accent hero__card">
            <p class="card__label"><?php esc_html_e('Gather With Us', 'church-theme'); ?></p>
            <h2><?php echo esc_html(church_theme_get_mod('location_name')); ?></h2>
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
                <p><?php esc_html_e('Add your first sermon to feature it here.', 'church-theme'); ?></p>
            </article>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="wrap callout">
        <div>
            <p class="eyebrow"><?php esc_html_e('Visit', 'church-theme'); ?></p>
            <h2><?php esc_html_e('Questions before Sunday?', 'church-theme'); ?></h2>
            <p><?php esc_html_e('Use the contact page to ask about service times, children’s ministry, prayer needs, or your first visit.', 'church-theme'); ?></p>
        </div>
        <a class="button" href="<?php echo esc_url(church_theme_get_page_url('contact')); ?>">
            <?php esc_html_e('Contact Us', 'church-theme'); ?>
        </a>
    </div>
</section>
<?php
get_footer();
