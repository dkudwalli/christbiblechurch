<?php
if (! defined('ABSPATH')) {
    exit;
}

$sections = $args['sections'] ?? [];
$nav_label = (string) ($args['nav_label'] ?? '');
$cta = is_array($args['cta'] ?? null) ? $args['cta'] : [];
?>
<?php if ($sections !== []) : ?>
    <section class="section section--muted section-nav-band">
        <div class="wrap">
            <?php
            get_template_part('template-parts/section', 'nav', [
                'sections' => $sections,
                'label' => $nav_label,
            ]);
            ?>
        </div>
    </section>

    <?php foreach ($sections as $index => $section) : ?>
        <?php
        get_template_part('template-parts/page', 'section', [
            'section' => $section,
            'index' => $index,
        ]);
        ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($cta !== []) : ?>
    <section class="section">
        <div class="wrap callout reveal">
            <div>
                <?php if (($cta['eyebrow'] ?? '') !== '') : ?>
                    <p class="eyebrow"><?php echo esc_html($cta['eyebrow']); ?></p>
                <?php endif; ?>
                <h2><?php echo esc_html($cta['heading'] ?? ''); ?></h2>
                <?php if (($cta['body'] ?? '') !== '') : ?>
                    <p><?php echo esc_html($cta['body']); ?></p>
                <?php endif; ?>
            </div>

            <div class="callout__actions">
                <?php if (($cta['primary_url'] ?? '') !== '') : ?>
                    <a class="button" href="<?php echo esc_url($cta['primary_url']); ?>">
                        <?php echo esc_html($cta['primary_label'] ?? ''); ?>
                    </a>
                <?php endif; ?>
                <?php if (($cta['secondary_url'] ?? '') !== '') : ?>
                    <a class="text-link" href="<?php echo esc_url($cta['secondary_url']); ?>">
                        <?php echo esc_html($cta['secondary_label'] ?? ''); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
