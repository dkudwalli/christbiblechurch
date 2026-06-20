<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

the_post();

$contact_phone = church_theme_get_mod('contact_phone');
$contact_email = church_theme_get_mod('contact_email');
?>
<?php
get_template_part('template-parts/page-hero', null, [
    'title' => get_the_title(),
    'summary' => __('Supporting the ministry and mission of Crossroad South Church through faithful giving.', 'church-theme'),
]);
?>

<section class="section">
    <div class="wrap content-grid">
        <div class="give-content">
            <article class="card give-scripture reveal">
                <blockquote>
                    <p><?php esc_html_e('Each of you should give what you have decided in your heart to give, not reluctantly or under compulsion, for God loves a cheerful giver.', 'church-theme'); ?></p>
                    <footer>
                        <cite><?php esc_html_e('2 Corinthians 9:7', 'church-theme'); ?></cite>
                    </footer>
                </blockquote>
            </article>

            <article class="card prose prose--wide reveal">
                <p class="eyebrow"><?php esc_html_e('How to Give', 'church-theme'); ?></p>
                <?php echo apply_filters('the_content', get_the_content()); ?>
            </article>
        </div>

        <div class="give-sidebar" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php
            $qr_image = church_theme_get_static_image(
                '/assets/images/crossroads/give-qr.png',
                __('Scan to Give QR Code', 'church-theme')
            );
            ?>
            <?php if ($qr_image !== null) : ?>
                <article class="card reveal" style="text-align: center;">
                    <p class="eyebrow" style="margin-bottom: 1rem;"><?php esc_html_e('Scan to Give', 'church-theme'); ?></p>
                    <div style="background: white; padding: 1rem; border-radius: 8px; display: inline-block; margin-bottom: 1rem;">
                        <?php
                        echo church_theme_render_static_image($qr_image, [
                            'loading' => 'lazy',
                            'decoding' => 'async',
                            'style' => 'width: 100%; max-width: 200px; height: auto; display: block;',
                        ]);
                        ?>
                    </div>
                    <p style="font-size: 0.9rem; color: var(--text-soft);"><?php esc_html_e('Use your preferred UPI app to scan and give directly.', 'church-theme'); ?></p>
                </article>
            <?php endif; ?>

            <div class="card card--accent content-aside reveal">
                <p class="card__label"><?php esc_html_e('Questions', 'church-theme'); ?></p>
                <h2><?php esc_html_e('Need help with a transfer?', 'church-theme'); ?></h2>
                <p><?php esc_html_e('Reach out to the church if you need confirmation, updated details, or support with giving to Crossroad South Church.', 'church-theme'); ?></p>

                <div class="content-aside__actions">
                    <?php if ($contact_phone !== '') : ?>
                        <a class="button button--secondary" href="tel:<?php echo esc_attr(church_theme_phone_href($contact_phone)); ?>">
                            <?php echo esc_html($contact_phone); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($contact_email !== '') : ?>
                        <a class="text-link" href="mailto:<?php echo esc_attr($contact_email); ?>">
                            <?php echo esc_html($contact_email); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
get_footer();
