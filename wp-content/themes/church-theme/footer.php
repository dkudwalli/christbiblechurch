<?php
if (! defined('ABSPATH')) {
    exit;
}

$contact_email = church_theme_get_mod('contact_email');
$contact_phone = church_theme_get_mod('contact_phone');
$worship_location = church_theme_split_lines(church_theme_get_mod('worship_location'));
$communication_address = church_theme_split_lines(church_theme_get_mod('communication_address'));
$brand_logo = church_theme_get_brand_logo_asset();
?>
</main>
<footer class="site-footer">
    <div class="wrap site-footer__inner">
        <div class="site-footer__brand">
            <img
                class="site-footer__mark"
                src="<?php echo esc_url((string) $brand_logo['src']); ?>"
                alt="<?php echo esc_attr((string) $brand_logo['alt']); ?>"
                width="<?php echo esc_attr((string) $brand_logo['width']); ?>"
                height="<?php echo esc_attr((string) $brand_logo['height']); ?>">
            <div class="site-footer__brand-copy">
                <p class="site-footer__title"><?php bloginfo('name'); ?></p>
                <p class="site-footer__copy"><?php echo esc_html(church_theme_get_mod('mission_statement')); ?></p>
                <p class="site-footer__copy"><?php echo esc_html(church_theme_get_mod('footer_invite')); ?></p>
            </div>
        </div>

        <div class="site-footer__meta">
            <?php if ($worship_location !== []) : ?>
                <div class="site-footer__column">
                    <p class="site-footer__label"><?php esc_html_e('Worship Location', 'church-theme'); ?></p>
                    <p><?php echo esc_html(implode(', ', $worship_location)); ?></p>
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
                    <p><?php echo esc_html(implode(', ', $communication_address)); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
