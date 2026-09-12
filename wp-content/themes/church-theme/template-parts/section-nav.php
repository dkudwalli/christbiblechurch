<?php
if (! defined('ABSPATH')) {
    exit;
}

$sections = $args['sections'] ?? [];
$label = (string) ($args['label'] ?? __('Page sections', 'church-theme'));

if (! is_array($sections) || $sections === []) {
    return;
}
?>
<nav class="section-nav" aria-label="<?php echo esc_attr($label); ?>">
    <details class="section-nav__disclosure" open>
        <summary class="section-nav__summary"><?php esc_html_e('On this page', 'church-theme'); ?></summary>
        <ul class="section-nav__list">
            <?php foreach ($sections as $section) : ?>
                <?php if (! $section instanceof WP_Post) : ?>
                    <?php continue; ?>
                <?php endif; ?>
                <li><a href="#<?php echo esc_attr(church_theme_get_section_anchor($section)); ?>"><?php echo esc_html(get_the_title($section)); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </details>
</nav>
