<?php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Standard page hero: optional breadcrumb, an <h1> title, and an optional summary.
 *
 * Expected $args:
 *   'title'        string  Heading text (escaped).
 *   'breadcrumbs'  array   Optional breadcrumb items (see template-parts/breadcrumb.php).
 *   'summary'      string  Optional plain-text summary, rendered as a paragraph.
 *   'content_html' string  Optional rich content (e.g. the_content), rendered raw in a prose block.
 *
 * Heroes with bespoke layouts (side panels, media grids, custom meta lines) keep
 * their own markup rather than using this part.
 */
$title = (string) ($args['title'] ?? '');
$breadcrumbs = $args['breadcrumbs'] ?? [];
$summary = (string) ($args['summary'] ?? '');
$content_html = (string) ($args['content_html'] ?? '');
?>
<section class="page-hero">
    <div class="wrap">
        <?php if (is_array($breadcrumbs) && $breadcrumbs !== []) : ?>
            <?php get_template_part('template-parts/breadcrumb', null, ['items' => $breadcrumbs]); ?>
        <?php endif; ?>
        <h1><?php echo esc_html($title); ?></h1>
        <?php if ($content_html !== '') : ?>
            <div class="page-hero__summary prose prose--compact">
                <?php echo $content_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php elseif ($summary !== '') : ?>
            <p class="page-hero__summary"><?php echo esc_html($summary); ?></p>
        <?php endif; ?>
    </div>
</section>
