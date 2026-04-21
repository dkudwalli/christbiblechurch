<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

the_post();

$contact_phone = church_theme_get_mod('contact_phone');
$contact_email = church_theme_get_mod('contact_email');
$service_times = church_theme_split_lines(church_theme_get_mod('service_times'));
$worship_location = church_theme_split_lines(church_theme_get_mod('worship_location'));
$communication_address = church_theme_split_lines(church_theme_get_mod('communication_address'));
$map_embed_url = church_theme_get_mod('map_embed_url');
?>
<section class="page-hero">
    <div class="wrap">
        <h1><?php the_title(); ?></h1>
        <div class="page-hero__summary prose prose--compact">
            <?php echo apply_filters('the_content', get_the_content()); ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap contact-page">
        <article class="card card--accent visit-note">
            <div>
                <p class="card__label"><?php esc_html_e('Plan Your Visit', 'church-theme'); ?></p>
                <h2><?php esc_html_e('Call or send a message before Sunday if you need help getting here.', 'church-theme'); ?></h2>
                <p><?php esc_html_e('We would be glad to help with directions, children’s ministry questions, or anything else that would make your first visit easier.', 'church-theme'); ?></p>
            </div>

            <?php if ($contact_phone !== '') : ?>
                <div class="visit-note__actions">
                    <a class="button button--secondary" href="tel:<?php echo esc_attr(church_theme_phone_href($contact_phone)); ?>">
                        <?php echo esc_html($contact_phone); ?>
                    </a>
                </div>
            <?php endif; ?>
        </article>

        <div class="contact-grid">
            <div class="card">
                <h2><?php esc_html_e('Visit and Connect', 'church-theme'); ?></h2>

                <div class="detail-list">
                    <?php if ($contact_email !== '') : ?>
                        <div class="detail-list__item">
                            <strong><?php esc_html_e('Email', 'church-theme'); ?></strong>
                            <p><a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($contact_phone !== '') : ?>
                        <div class="detail-list__item">
                            <strong><?php esc_html_e('Phone', 'church-theme'); ?></strong>
                            <p><a href="tel:<?php echo esc_attr(church_theme_phone_href($contact_phone)); ?>"><?php echo esc_html($contact_phone); ?></a></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($service_times !== []) : ?>
                        <div class="detail-list__item">
                            <strong><?php esc_html_e('Timings', 'church-theme'); ?></strong>
                            <p><?php echo esc_html(implode(' | ', $service_times)); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($worship_location !== []) : ?>
                        <div class="detail-list__item">
                            <strong><?php esc_html_e('Worship Location', 'church-theme'); ?></strong>
                            <p><?php echo esc_html(implode(', ', $worship_location)); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($communication_address !== []) : ?>
                        <div class="detail-list__item">
                            <strong><?php esc_html_e('Communication Address', 'church-theme'); ?></strong>
                            <p><?php echo esc_html(implode(', ', $communication_address)); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h2><?php echo esc_html(church_theme_get_mod('contact_form_heading')); ?></h2>
                <?php
                if (shortcode_exists('church_contact_form')) {
                    echo do_shortcode('[church_contact_form]');
                } else {
                    echo '<p>' . esc_html__('Activate the church-core plugin to enable the contact form.', 'church-theme') . '</p>';
                }
                ?>
            </div>
        </div>
    </div>
</section>

<?php if ($map_embed_url !== '') : ?>
    <section class="section section--muted">
        <div class="wrap">
            <div class="map-frame">
                <iframe
                    src="<?php echo esc_url($map_embed_url); ?>"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen
                    title="<?php esc_attr_e('Church location map', 'church-theme'); ?>"></iframe>
            </div>
        </div>
    </section>
<?php endif; ?>
<?php
get_footer();
