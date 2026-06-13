<?php
if (! defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();
$series_term = church_theme_get_sermon_primary_term($post_id, 'series');
$speaker_term = church_theme_get_sermon_primary_term($post_id, 'speaker');
$scripture = (string) get_post_meta($post_id, 'scripture_reference', true);
$youtube_id = (string) get_post_meta($post_id, 'youtube_video_id', true);
?>
<article class="card sermon-card">
    <?php if ($youtube_id !== '') : ?>
        <a class="sermon-card__thumb" href="<?php echo esc_url(church_theme_get_sermon_url($post_id)); ?>" tabindex="-1" aria-hidden="true">
            <img
                src="<?php echo esc_url('https://i.ytimg.com/vi/' . $youtube_id . '/hqdefault.jpg'); ?>"
                alt=""
                loading="lazy"
                decoding="async"
                width="480"
                height="360">
        </a>
    <?php endif; ?>
    <p class="eyebrow"><?php echo esc_html(church_theme_get_sermon_date($post_id)); ?></p>
    <h2><a href="<?php echo esc_url(church_theme_get_sermon_url($post_id)); ?>"><?php the_title(); ?></a></h2>

    <p class="sermon-meta">
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

    <p><?php echo esc_html(church_theme_get_sermon_excerpt_preview($post_id)); ?></p>

    <a class="text-link" href="<?php echo esc_url(church_theme_get_sermon_url($post_id)); ?>">
        <?php esc_html_e('Open sermon', 'church-theme'); ?>
    </a>
</article>
