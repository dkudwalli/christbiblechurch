<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    $post_id = get_the_ID();
    $scripture = (string) get_post_meta($post_id, 'scripture_reference', true);
    $youtube_url = (string) get_post_meta($post_id, 'youtube_url', true);
    $audio_url = church_theme_get_sermon_audio_url($post_id);
    $summary_content = get_the_content();
    $has_summary = trim(wp_strip_all_tags($summary_content)) !== '';
    $has_media = $youtube_url !== '' || $audio_url !== '';
    $speaker_term = church_theme_get_sermon_primary_term($post_id, 'speaker');
    $series_term = church_theme_get_sermon_primary_term($post_id, 'series');
    $related = church_theme_get_related_sermon_query($post_id);
    $related_sermons = $related['query'];
    $related_section_title = $related['title'];
    ?>
    <section class="page-hero">
        <div class="wrap">
            <nav aria-label="Breadcrumb" class="breadcrumbs" style="font-size: 0.85rem; font-weight: 600; margin-bottom: 1rem; color: var(--text-soft);">
                <a href="<?php echo esc_url(home_url('/')); ?>" style="color: var(--accent-strong); text-decoration: none;">Home</a>
                <span style="margin: 0 0.4rem; opacity: 0.5;">/</span>
                <a href="<?php echo esc_url(church_theme_get_sermon_archive_url()); ?>" style="color: var(--accent-strong); text-decoration: none;">Sermons</a>
                <?php if ($series_term) : ?>
                    <span style="margin: 0 0.4rem; opacity: 0.5;">/</span>
                    <a href="<?php echo esc_url(church_theme_get_sermon_term_url($series_term)); ?>" style="color: var(--accent-strong); text-decoration: none;"><?php echo esc_html($series_term->name); ?></a>
                <?php endif; ?>
                <span style="margin: 0 0.4rem; opacity: 0.5;">/</span>
                <span aria-current="page"><?php echo esc_html(wp_trim_words(get_the_title(), 5)); ?></span>
            </nav>
            <h1><?php the_title(); ?></h1>
            <p class="sermon-meta sermon-meta--hero">
                <span><?php echo esc_html(church_theme_get_sermon_date($post_id)); ?></span>
                <?php if ($series_term) : ?>
                    <span><a href="<?php echo esc_url(church_theme_get_sermon_term_url($series_term)); ?>"><?php echo esc_html($series_term->name); ?></a></span>
                <?php endif; ?>
                <?php if ($speaker_term) : ?>
                    <span><?php echo esc_html($speaker_term->name); ?></span>
                <?php endif; ?>
                <?php if ($scripture !== '') : ?>
                    <span><?php echo esc_html($scripture); ?></span>
                <?php endif; ?>
            </p>
        </div>
    </section>

    <?php if ($youtube_url !== '') : ?>
        <section class="section single-sermon-media-section">
            <div class="wrap">
                <div class="video-frame video-frame--wide reveal">
                    <?php
                    $embed_html = wp_oembed_get($youtube_url);

                    if ($embed_html) {
                        echo $embed_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    } else {
                        ?>
                        <div class="video-frame__fallback">
                            <p><?php esc_html_e('Watch this sermon on YouTube.', 'church-theme'); ?></p>
                            <a class="button button--secondary" href="<?php echo esc_url($youtube_url); ?>" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Open Video', 'church-theme'); ?>
                            </a>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="section">
        <div class="wrap single-sermon">
            <div class="single-sermon__content">

                <?php if ($audio_url !== '') : ?>
                    <div class="audio-player card reveal">
                        <?php $audio_heading_id = 'sermon-audio-heading-' . $post_id; ?>
                        <h2 id="<?php echo esc_attr($audio_heading_id); ?>"><?php esc_html_e('Listen', 'church-theme'); ?></h2>
                        <audio class="sermon-audio" controls preload="none" aria-labelledby="<?php echo esc_attr($audio_heading_id); ?>">
                            <source src="<?php echo esc_url($audio_url); ?>">
                            <?php esc_html_e('Your browser does not support audio playback.', 'church-theme'); ?>
                            <a href="<?php echo esc_url($audio_url); ?>" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Open audio directly', 'church-theme'); ?>
                            </a>
                        </audio>
                        <p class="audio-player__link">
                            <a class="text-link" href="<?php echo esc_url($audio_url); ?>" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Open audio directly', 'church-theme'); ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                <section class="single-sermon__meta-grid reveal-stagger" aria-label="<?php esc_attr_e('Sermon details', 'church-theme'); ?>">
                    <article class="card single-sermon__meta-card reveal">
                        <p class="eyebrow"><?php esc_html_e('Date', 'church-theme'); ?></p>
                        <h2><?php echo esc_html(church_theme_get_sermon_date($post_id)); ?></h2>
                    </article>

                    <?php if ($series_term) : ?>
                        <article class="card single-sermon__meta-card">
                            <p class="eyebrow"><?php esc_html_e('Series', 'church-theme'); ?></p>
                            <h2><a href="<?php echo esc_url(church_theme_get_sermon_term_url($series_term)); ?>"><?php echo esc_html($series_term->name); ?></a></h2>
                        </article>
                    <?php endif; ?>

                    <?php if ($speaker_term) : ?>
                        <article class="card single-sermon__meta-card">
                            <p class="eyebrow"><?php esc_html_e('Preacher', 'church-theme'); ?></p>
                            <h2><?php echo esc_html($speaker_term->name); ?></h2>
                        </article>
                    <?php endif; ?>

                    <?php if ($scripture !== '') : ?>
                        <article class="card single-sermon__meta-card">
                            <p class="eyebrow"><?php esc_html_e('Scripture', 'church-theme'); ?></p>
                            <h2><?php echo esc_html($scripture); ?></h2>
                        </article>
                    <?php endif; ?>
                </section>

                <?php if ($has_summary) : ?>
                    <article class="card single-sermon__summary reveal">
                        <p class="eyebrow"><?php esc_html_e('Summary Notes', 'church-theme'); ?></p>
                        <div class="prose prose--wide">
                            <?php echo apply_filters('the_content', $summary_content); ?>
                        </div>
                    </article>
                <?php elseif (! $has_media) : ?>
                    <article class="card single-sermon__summary">
                        <p class="eyebrow"><?php esc_html_e('Sermon Notes', 'church-theme'); ?></p>
                        <p><?php esc_html_e('Video, audio, or written notes have not been added for this sermon yet.', 'church-theme'); ?></p>
                    </article>
                <?php endif; ?>

                <article class="card single-sermon__actions reveal">
                    <div>
                        <p class="eyebrow"><?php esc_html_e('Explore', 'church-theme'); ?></p>
                        <h2><?php esc_html_e('Keep Listening', 'church-theme'); ?></h2>
                        <p><?php esc_html_e('Browse the full sermon archive or share this message with someone in your church community.', 'church-theme'); ?></p>
                    </div>
                    <a class="button button--secondary" href="<?php echo esc_url(church_theme_get_sermon_archive_url()); ?>">
                        <?php esc_html_e('Back to all sermons', 'church-theme'); ?>
                    </a>
                </article>
            </div>
        </div>
    </section>

    <?php if ($related_sermons->have_posts()) : ?>
        <section class="section">
            <div class="wrap">
                <div class="section-heading reveal">
                    <p class="eyebrow"><?php esc_html_e('Keep Listening', 'church-theme'); ?></p>
                    <h2><?php echo esc_html($related_section_title); ?></h2>
                </div>

                <div class="sermon-grid reveal-stagger">
                    <?php while ($related_sermons->have_posts()) : $related_sermons->the_post(); ?>
                        <?php get_template_part('template-parts/sermon', 'card'); ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
<?php endwhile; ?>
<?php
get_footer();
