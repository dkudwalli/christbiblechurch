<?php
if (! defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();
$cover_image = church_theme_get_photo_album_cover_asset($post_id);
$summary = church_theme_get_post_preview($post_id);
?>
<article class="card album-card reveal">
    <?php if ($cover_image !== null) : ?>
        <a class="album-card__thumb" href="<?php echo esc_url(church_theme_get_photo_album_url($post_id)); ?>" tabindex="-1" aria-hidden="true">
            <img
                src="<?php echo esc_url((string) $cover_image['src']); ?>"
                <?php if (! empty($cover_image['srcset'])) : ?>
                srcset="<?php echo esc_attr((string) $cover_image['srcset']); ?>"
                sizes="(max-width: 720px) 100vw, 420px"
                <?php endif; ?>
                alt=""
                loading="lazy"
                decoding="async"
                width="<?php echo (int) ($cover_image['width'] ?? 0); ?>"
                height="<?php echo (int) ($cover_image['height'] ?? 0); ?>">
        </a>
    <?php endif; ?>

    <p class="eyebrow"><?php echo esc_html(church_theme_get_photo_album_date($post_id)); ?></p>
    <h2><a href="<?php echo esc_url(church_theme_get_photo_album_url($post_id)); ?>"><?php the_title(); ?></a></h2>

    <?php if ($summary !== '') : ?>
        <p><?php echo esc_html($summary); ?></p>
    <?php endif; ?>

    <a class="text-link" href="<?php echo esc_url(church_theme_get_photo_album_url($post_id)); ?>">
        <?php esc_html_e('View album', 'church-theme'); ?>
    </a>
</article>
