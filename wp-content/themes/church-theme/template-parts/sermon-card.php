<?php
if (! defined('ABSPATH')) {
    exit;
}

$speaker_terms = get_the_terms(get_the_ID(), 'speaker');
$scripture = (string) get_post_meta(get_the_ID(), 'scripture_reference', true);
?>
<article class="card sermon-card">
    <p class="eyebrow"><?php echo esc_html(church_theme_get_sermon_date(get_the_ID())); ?></p>
    <h2><a href="<?php echo esc_url(church_theme_get_sermon_url(get_the_ID())); ?>"><?php the_title(); ?></a></h2>

    <p class="sermon-meta">
        <?php if ($speaker_terms && ! is_wp_error($speaker_terms)) : ?>
            <span><?php echo esc_html($speaker_terms[0]->name); ?></span>
        <?php endif; ?>
        <?php if ($scripture !== '') : ?>
            <span><?php echo esc_html($scripture); ?></span>
        <?php endif; ?>
    </p>

    <p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 24)); ?></p>

    <a class="text-link" href="<?php echo esc_url(church_theme_get_sermon_url(get_the_ID())); ?>">
        <?php esc_html_e('Open sermon', 'church-theme'); ?>
    </a>
</article>
