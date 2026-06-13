<?php
if (! defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();
$event_location = church_theme_get_event_location($post_id);
$event_preview = church_theme_get_event_notes_preview($post_id, 24);
?>
<article class="card event-card reveal">
    <?php if (has_post_thumbnail($post_id)) : ?>
        <a class="event-card__thumb" href="<?php echo esc_url(church_theme_get_event_url($post_id)); ?>" tabindex="-1" aria-hidden="true">
            <?php
            echo wp_get_attachment_image(
                (int) get_post_thumbnail_id($post_id),
                'medium',
                false,
                ['loading' => 'lazy', 'decoding' => 'async']
            );
            ?>
        </a>
    <?php endif; ?>
    <p class="eyebrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -2px; margin-right: 4px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg><?php echo esc_html(church_theme_get_event_datetime($post_id)); ?></p>
    <h2><a href="<?php echo esc_url(church_theme_get_event_url($post_id)); ?>"><?php the_title(); ?></a></h2>

    <?php if ($event_location !== '') : ?>
        <p class="event-card__meta">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -2px; margin-right: 2px; opacity: 0.8;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
            <strong><?php esc_html_e('Location:', 'church-theme'); ?></strong>
            <span><?php echo esc_html($event_location); ?></span>
        </p>
    <?php endif; ?>

    <?php if ($event_preview !== '') : ?>
        <p><?php echo esc_html($event_preview); ?></p>
    <?php endif; ?>

    <a class="text-link" href="<?php echo esc_url(church_theme_get_event_url($post_id)); ?>">
        <?php esc_html_e('View event', 'church-theme'); ?>
    </a>
</article>
