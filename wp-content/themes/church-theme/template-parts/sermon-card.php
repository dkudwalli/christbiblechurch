<?php
if (! defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();
$series_term = church_theme_get_sermon_primary_term($post_id, 'series');
$speaker_term = church_theme_get_sermon_primary_term($post_id, 'speaker');
$scripture = (string) get_post_meta($post_id, 'scripture_reference', true);
$card_image = church_theme_get_sermon_card_image($post_id);
?>
<article class="card sermon-card reveal">
    <?php if ($card_image !== null) : ?>
        <a class="sermon-card__thumb" href="<?php echo esc_url(church_theme_get_sermon_url($post_id)); ?>" tabindex="-1" aria-hidden="true">
            <img
                src="<?php echo esc_url($card_image['src']); ?>"
                <?php if ($card_image['srcset'] !== '') : ?>
                srcset="<?php echo esc_attr($card_image['srcset']); ?>"
                sizes="(max-width: 600px) 100vw, 360px"
                <?php endif; ?>
                alt=""
                loading="lazy"
                decoding="async"
                width="<?php echo (int) $card_image['width']; ?>"
                height="<?php echo (int) $card_image['height']; ?>">
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

    <p><?php echo esc_html(church_theme_get_post_preview($post_id, 24)); ?></p>

    <a class="text-link" href="<?php echo esc_url(church_theme_get_sermon_url($post_id)); ?>">
        <?php esc_html_e('Open sermon', 'church-theme'); ?>
    </a>
</article>
