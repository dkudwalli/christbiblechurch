<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

the_post();

$contact_phone = church_theme_get_mod('contact_phone');
$contact_email = church_theme_get_mod('contact_email');
?>
<section class="page-hero">
    <div class="wrap">
        <h1><?php the_title(); ?></h1>
        <p class="page-hero__summary"><?php esc_html_e('Supporting the ministry and mission of Crossroad South Church through faithful giving.', 'church-theme'); ?></p>
    </div>
</section>

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
</section>
<?php
get_footer();
