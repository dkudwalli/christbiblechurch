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
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -2px; margin-right: 4px; opacity: 0.8;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <?php foreach ($worship_location as $line) : ?>
                                <span class="site-footer__line"><?php echo esc_html($line); ?></span>
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($contact_phone !== '') : ?>
                        <p><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -3px; margin-right: 4px; opacity: 0.8;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg><a href="tel:<?php echo esc_attr(church_theme_phone_href($contact_phone)); ?>"><?php echo esc_html($contact_phone); ?></a></p>
                    <?php endif; ?>

                    <?php if ($contact_email !== '') : ?>
                        <p><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -3px; margin-right: 4px; opacity: 0.8;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg><a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($communication_address !== []) : ?>
                <div class="site-footer__column">
                    <p class="site-footer__label"><?php esc_html_e('Mailing Address', 'church-theme'); ?></p>
                    <p class="site-footer__lines">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -2px; margin-right: 4px; opacity: 0.8;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
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
