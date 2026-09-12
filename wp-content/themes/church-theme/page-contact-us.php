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
$map_directions_url = church_theme_get_map_directions_url();
$worship_location_name = $worship_location[0] ?? __('our worship hall', 'church-theme');
?>
<?php get_template_part('template-parts/page-hero', null, [
    'title' => get_the_title(),
    'content_html' => apply_filters('the_content', get_the_content()),
]); ?>

<section class="section contact-practical">
    <div class="wrap contact-page">
        <article class="card card--accent contact-panel contact-panel--details reveal">
            <div class="contact-panel__header">
                <p class="eyebrow"><?php esc_html_e('Visit and Connect', 'church-theme'); ?></p>
                <h2><?php esc_html_e('Everything you need for Sunday.', 'church-theme'); ?></h2>
            </div>
            <div class="contact-direct-actions">
                <?php if ($map_directions_url !== '') : ?>
                    <a class="button" href="<?php echo esc_url($map_directions_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Get Directions', 'church-theme'); ?></a>
                <?php endif; ?>
                <?php if ($contact_phone !== '') : ?>
                    <a class="button button--secondary" href="tel:<?php echo esc_attr(church_theme_phone_href($contact_phone)); ?>"><?php printf(esc_html__('Call %s', 'church-theme'), esc_html($contact_phone)); ?></a>
                <?php endif; ?>
                <?php if ($contact_email !== '') : ?>
                    <a class="text-link" href="mailto:<?php echo esc_attr($contact_email); ?>"><?php esc_html_e('Email the Church', 'church-theme'); ?></a>
                <?php endif; ?>
            </div>
            <dl class="detail-list detail-list--contact">
                <?php if ($service_times !== []) : ?>
                    <div class="detail-list__item">
                        <dt><?php echo church_theme_icon('clock', ['size' => 14]); ?><?php esc_html_e('Service Times', 'church-theme'); ?></dt>
                        <dd>
                            <ul class="stack-list">
                                <?php foreach ($service_times as $service_time) : ?>
                                    <li><?php echo esc_html($service_time); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </dd>
                    </div>
                <?php endif; ?>
                <?php if ($worship_location !== []) : ?>
                    <div class="detail-list__item">
                        <dt><?php echo church_theme_icon('location', ['size' => 14]); ?><?php esc_html_e('Worship Location', 'church-theme'); ?></dt>
                        <dd><?php echo esc_html(implode(', ', $worship_location)); ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </article>

        <article class="card contact-panel contact-panel--form reveal">
            <div class="contact-panel__header">
                <p class="eyebrow"><?php esc_html_e('Send a Message', 'church-theme'); ?></p>
                <h2><?php echo esc_html(church_theme_get_mod('contact_form_heading')); ?></h2>
            </div>
            <?php
            if (shortcode_exists('church_contact_form')) {
                echo do_shortcode('[church_contact_form]');
            } else {
                echo '<p>' . esc_html__('Activate the church-core plugin to enable the contact form.', 'church-theme') . '</p>';
            }
            ?>
        </article>
    </div>
</section>

<?php if ($map_embed_url !== '' || $map_directions_url !== '') : ?>
    <section class="section section--muted">
        <div class="wrap contact-map-section">
            <div class="section-heading contact-map-section__heading reveal">
                <p class="eyebrow"><?php esc_html_e('Location', 'church-theme'); ?></p>
                <h2><?php esc_html_e('Find the worship hall.', 'church-theme'); ?></h2>
                <p><?php printf(esc_html__('We gather at %s each Sunday.', 'church-theme'), esc_html($worship_location_name)); ?></p>
                <?php if ($map_directions_url !== '') : ?>
                    <a class="button button--secondary" href="<?php echo esc_url($map_directions_url); ?>" target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e('Open in Google Maps', 'church-theme'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="map-frame reveal">
                <?php if ($map_embed_url !== '') : ?>
                    <?php if ($map_directions_url !== '') : ?>
                        <div class="map-frame__fallback">
                            <p><?php esc_html_e('If the embedded map does not appear, use Get Directions above.', 'church-theme'); ?></p>
                        </div>
                    <?php endif; ?>
                    <iframe src="<?php echo esc_url($map_embed_url); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen title="<?php esc_attr_e('Church location map', 'church-theme'); ?>"></iframe>
                <?php else : ?>
                    <div class="map-frame__placeholder">
                        <p><?php esc_html_e('The map embed is unavailable right now. Use Get Directions above.', 'church-theme'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($communication_address !== []) : ?>
    <section class="section contact-secondary">
        <div class="wrap">
            <article class="card reveal">
                <p class="card__label"><?php esc_html_e('Office and Mailing', 'church-theme'); ?></p>
                <h2><?php esc_html_e('Communication address', 'church-theme'); ?></h2>
                <p><?php echo esc_html(implode(', ', $communication_address)); ?></p>
            </article>
        </div>
    </section>
<?php endif; ?>
<?php get_footer();
