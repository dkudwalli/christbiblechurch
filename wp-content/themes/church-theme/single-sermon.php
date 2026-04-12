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
    $speakers = get_the_terms($post_id, 'speaker');
    $related_sermons = new WP_Query([
        'post_type' => 'sermon',
        'posts_per_page' => 3,
        'post__not_in' => [$post_id],
        'meta_key' => 'sermon_date',
        'orderby' => 'meta_value',
        'order' => 'DESC',
    ]);
    ?>
    <section class="page-hero">
        <div class="wrap">
            <p class="eyebrow"><?php esc_html_e('Sermon', 'church-theme'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="sermon-meta sermon-meta--hero">
                <span><?php echo esc_html(church_theme_get_sermon_date($post_id)); ?></span>
                <?php if ($speakers && ! is_wp_error($speakers)) : ?>
                    <span><?php echo esc_html($speakers[0]->name); ?></span>
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

                    <?php if ($speakers && ! is_wp_error($speakers)) : ?>
                        <article class="card single-sermon__meta-card">
                            <p class="eyebrow"><?php esc_html_e('Preacher', 'church-theme'); ?></p>
                            <h2><?php echo esc_html($speakers[0]->name); ?></h2>
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
                    <a class="button button--secondary" href="<?php echo esc_url(get_post_type_archive_link('sermon') ?: home_url('/sermons/')); ?>">
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
                    <p class="eyebrow"><?php esc_html_e('More Teaching', 'church-theme'); ?></p>
                    <h2><?php esc_html_e('Recent Sermons', 'church-theme'); ?></h2>
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
