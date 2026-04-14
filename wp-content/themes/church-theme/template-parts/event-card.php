<?php
if (! defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();
$event_location = church_theme_get_event_location($post_id);
$event_preview = church_theme_get_event_notes_preview($post_id, 24);
?>
<article class="card event-card">
    <p class="eyebrow"><?php echo esc_html(church_theme_get_event_datetime($post_id)); ?></p>
    <h2><a href="<?php echo esc_url(church_theme_get_event_url($post_id)); ?>"><?php the_title(); ?></a></h2>

    <?php if ($event_location !== '') : ?>
        <p class="event-card__meta">
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
