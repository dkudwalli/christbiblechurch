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
    $related_section_title = __('Recent Sermons', 'church-theme');
    $related_query_args = [
        'post_type' => 'sermon',
        'posts_per_page' => 3,
        'post__not_in' => [$post_id],
        'meta_key' => 'sermon_date',
        'orderby' => 'meta_value',
        'order' => 'DESC',
    ];

    if ($series_term) {
        $related_query_args['tax_query'] = [[
            'taxonomy' => 'series',
            'field' => 'term_id',
            'terms' => [$series_term->term_id],
        ]];
    }

    $related_sermons = new WP_Query($related_query_args);

    if ($series_term && $related_sermons->have_posts()) {
        $related_section_title = sprintf(__('More in %s', 'church-theme'), $series_term->name);
    }

    if ($series_term && ! $related_sermons->have_posts()) {
        $related_sermons = new WP_Query([
            'post_type' => 'sermon',
            'posts_per_page' => 3,
            'post__not_in' => [$post_id],
            'meta_key' => 'sermon_date',
            'orderby' => 'meta_value',
            'order' => 'DESC',
        ]);
    }
    ?>
    <section class="page-hero">
        <div class="wrap">
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

    <section class="section">
        <div class="wrap single-sermon">
            <div class="single-sermon__content">
                <?php if ($youtube_url !== '') : ?>
                    <div class="video-frame">
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
                <?php endif; ?>

                <?php if ($audio_url !== '') : ?>
                    <div class="audio-player card">
                        <h2><?php esc_html_e('Listen', 'church-theme'); ?></h2>
                        <?php echo wp_audio_shortcode(['src' => esc_url($audio_url)]); ?>
                    </div>
                <?php endif; ?>

                <section class="single-sermon__meta-grid" aria-label="<?php esc_attr_e('Sermon details', 'church-theme'); ?>">
                    <article class="card single-sermon__meta-card">
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
                    <article class="card single-sermon__summary">
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

                <article class="card single-sermon__actions">
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
                <div class="section-heading">
                    <h2><?php echo esc_html($related_section_title); ?></h2>
                </div>

                <div class="sermon-grid">
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
