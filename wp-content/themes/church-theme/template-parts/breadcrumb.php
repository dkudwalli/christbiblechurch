<?php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Breadcrumb navigation.
 *
 * Expects $args['items']: an ordered array of ['label' => string, 'url' => string].
 * An item with a non-empty 'url' renders as a link; an item without one renders
 * as the current page. Separators are drawn via the `.breadcrumbs` CSS rules.
 */
$items = $args['items'] ?? [];

if (! is_array($items) || $items === []) {
    return;
}
?>
<nav aria-label="Breadcrumb" class="breadcrumbs">
    <?php
    foreach ($items as $item) :
        $label = (string) ($item['label'] ?? '');
        $url = (string) ($item['url'] ?? '');
        ?>
        <?php if ($url !== '') : ?>
            <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
        <?php else : ?>
            <span aria-current="page"><?php echo esc_html($label); ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
