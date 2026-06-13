<?php
if (! defined('ABSPATH')) {
    exit;
}

$contact_email = church_theme_get_mod('contact_email');
$contact_phone = church_theme_get_mod('contact_phone');
$worship_location = church_theme_split_lines(church_theme_get_mod('worship_location'));
$communication_address = church_theme_split_lines(church_theme_get_mod('communication_address'));
$brand_logo = church_theme_get_brand_logo_asset();
$footer_mission_line = church_theme_get_mod('footer_mission_line');
?>
</main>
<footer class="site-footer">
    <div class="wrap site-footer__inner">
        <div class="site-footer__brand">
            <?php
            echo church_theme_render_static_image($brand_logo, [
                'class' => 'site-footer__mark',
                'sizes' => '(max-width: 720px) 180px, 220px',
                'loading' => 'lazy',
            ]);
            ?>
            <div class="site-footer__brand-copy">
                <?php if ($footer_mission_line !== '') : ?>
                    <p class="site-footer__copy site-footer__mission">
                        <?php foreach (church_theme_split_lines($footer_mission_line) as $line) : ?>
                            <span class="site-footer__mission-line"><?php echo esc_html($line); ?></span>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>
                <?php $footer_invite = church_theme_get_mod('footer_invite'); ?>
                <?php if ($footer_invite !== '') : ?>
                    <p class="site-footer__copy"><?php echo esc_html($footer_invite); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="site-footer__meta">
            <?php if ($worship_location !== []) : ?>
                <div class="site-footer__column">
                    <p class="site-footer__label"><?php esc_html_e('Worship Location', 'church-theme'); ?></p>
                    <p class="site-footer__lines">
                        <?php foreach ($worship_location as $line) : ?>
                            <span class="site-footer__line"><?php echo esc_html($line); ?></span>
                        <?php endforeach; ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="site-footer__column">
                <p class="site-footer__label"><?php esc_html_e('Contact', 'church-theme'); ?></p>

                <?php if ($contact_phone !== '') : ?>
                    <p><a href="tel:<?php echo esc_attr(church_theme_phone_href($contact_phone)); ?>"><?php echo esc_html($contact_phone); ?></a></p>
                <?php endif; ?>

                <?php if ($contact_email !== '') : ?>
                    <p><a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></p>
                <?php endif; ?>
            </div>

            <?php if ($communication_address !== []) : ?>
                <div class="site-footer__column">
                    <p class="site-footer__label"><?php esc_html_e('Communication Address', 'church-theme'); ?></p>
                    <p class="site-footer__lines">
                        <?php foreach ($communication_address as $line) : ?>
                            <span class="site-footer__line"><?php echo esc_html($line); ?></span>
                        <?php endforeach; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
