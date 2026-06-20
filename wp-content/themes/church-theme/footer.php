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
$instagram_url = church_theme_get_instagram_profile_url();
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
            <div class="site-footer__column">
                <p class="site-footer__label"><?php esc_html_e('Explore', 'church-theme'); ?></p>
                <nav class="site-footer__nav" aria-label="<?php esc_attr_e('Footer navigation', 'church-theme'); ?>">
                    <ul>
                        <li><a href="<?php echo esc_url(church_theme_get_page_url('about-us')); ?>"><?php esc_html_e('About Us', 'church-theme'); ?></a></li>
                        <li><a href="<?php echo esc_url(church_theme_get_page_url('worship')); ?>"><?php esc_html_e('Worship', 'church-theme'); ?></a></li>
                        <li><a href="<?php echo esc_url(church_theme_get_sermon_archive_url()); ?>"><?php esc_html_e('Sermons', 'church-theme'); ?></a></li>
                        <li><a href="<?php echo esc_url(church_theme_get_event_archive_url()); ?>"><?php esc_html_e('Events', 'church-theme'); ?></a></li>
                        <li><a href="<?php echo esc_url(church_theme_get_page_url('gallery')); ?>"><?php esc_html_e('Gallery', 'church-theme'); ?></a></li>
                        <li><a href="<?php echo esc_url(church_theme_get_page_url('give')); ?>"><?php esc_html_e('Give', 'church-theme'); ?></a></li>
                        <li><a href="<?php echo esc_url(church_theme_get_page_url('contact-us')); ?>"><?php esc_html_e('Contact', 'church-theme'); ?></a></li>
                    </ul>
                </nav>
            </div>

            <?php if ($worship_location !== [] || $contact_phone !== '' || $contact_email !== '') : ?>
                <div class="site-footer__column">
                    <p class="site-footer__label"><?php esc_html_e('Visit & Connect', 'church-theme'); ?></p>

                    <?php if ($worship_location !== []) : ?>
                        <p class="site-footer__lines">
                            <?php echo church_theme_icon('location'); ?>
                            <?php foreach ($worship_location as $line) : ?>
                                <span class="site-footer__line"><?php echo esc_html($line); ?></span>
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($contact_phone !== '') : ?>
                        <p><?php echo church_theme_icon('phone'); ?><a href="tel:<?php echo esc_attr(church_theme_phone_href($contact_phone)); ?>"><?php echo esc_html($contact_phone); ?></a></p>
                    <?php endif; ?>

                    <?php if ($contact_email !== '') : ?>
                        <p><?php echo church_theme_icon('envelope'); ?><a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($communication_address !== []) : ?>
                <div class="site-footer__column">
                    <p class="site-footer__label"><?php esc_html_e('Mailing Address', 'church-theme'); ?></p>
                    <p class="site-footer__lines">
                        <?php echo church_theme_icon('address'); ?>
                        <?php foreach ($communication_address as $line) : ?>
                            <span class="site-footer__line"><?php echo esc_html($line); ?></span>
                        <?php endforeach; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="wrap site-footer__bottom">
        <?php if ($instagram_url !== '') : ?>
            <a class="site-footer__social" href="<?php echo esc_url($instagram_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Follow us on Instagram', 'church-theme'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="5"/><circle cx="17.5" cy="6.5" r="1.5"/></svg>
                <span><?php esc_html_e('Instagram', 'church-theme'); ?></span>
            </a>
        <?php endif; ?>

        <p class="site-footer__copyright">
            &copy; <?php echo esc_html(gmdate('Y')); ?>
            <?php esc_html_e('Crossroad South Church. All rights reserved.', 'church-theme'); ?>
        </p>
    </div>
    <button class="back-to-top" aria-label="<?php esc_attr_e('Back to top', 'church-theme'); ?>" tabindex="-1">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="18 15 12 9 6 15"></polyline></svg>
    </button>
</footer>
<?php wp_footer(); ?>
</body>
</html>
