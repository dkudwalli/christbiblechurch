<?php
if (! defined('ABSPATH')) {
    exit;
}

$contact_email = church_theme_get_mod('contact_email');
$contact_phone = church_theme_get_mod('contact_phone');
$contact_address = church_theme_split_lines(church_theme_get_mod('contact_address'));
?>
</main>
<footer class="site-footer">
    <div class="wrap site-footer__inner">
        <div class="site-footer__brand">
            <img
                class="site-footer__mark"
                src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo-fav.svg'); ?>"
                alt="<?php esc_attr_e('Christ Bible Church logo', 'church-theme'); ?>"
                width="56"
                height="56">
            <div class="site-footer__brand-copy">
                <p class="site-footer__title"><?php bloginfo('name'); ?></p>
                <p class="site-footer__copy"><?php echo esc_html(church_theme_get_mod('mission_statement')); ?></p>
            </div>
        </div>

        <div class="site-footer__meta">
            <?php if ($contact_phone !== '') : ?>
                <p><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $contact_phone)); ?>"><?php echo esc_html($contact_phone); ?></a></p>
            <?php endif; ?>

            <?php if ($contact_email !== '') : ?>
                <p><a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></p>
            <?php endif; ?>

            <?php if ($contact_address !== []) : ?>
                <p><?php echo esc_html(implode(', ', $contact_address)); ?></p>
            <?php endif; ?>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
