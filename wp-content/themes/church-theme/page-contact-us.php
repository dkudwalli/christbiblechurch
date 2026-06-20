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
$has_direct_actions = $contact_phone !== '' || $contact_email !== '';
?>
<?php
get_template_part('template-parts/page-hero', null, [
    'title' => get_the_title(),
    'content_html' => apply_filters('the_content', get_the_content()),
]);
?>

<section class="section">
    <div class="wrap contact-page">
        <article class="card card--accent visit-note reveal">
            <div class="visit-note__copy">
                <p class="card__label"><?php esc_html_e('Plan Your Visit', 'church-theme'); ?></p>
                <h2><?php esc_html_e('Tell us what would make your first Sunday easier.', 'church-theme'); ?></h2>
                <p><?php esc_html_e('Reach out for directions, children’s ministry questions, service-time details, or anything else you want to clarify before visiting Crossroad South Church.', 'church-theme'); ?></p>
            </div>

            <?php if ($has_direct_actions) : ?>
                <div class="visit-note__actions">
                    <?php if ($contact_phone !== '') : ?>
                        <a class="button" href="tel:<?php echo esc_attr(church_theme_phone_href($contact_phone)); ?>">
                            <?php echo esc_html($contact_phone); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($contact_email !== '') : ?>
                        <a class="button button--secondary" href="mailto:<?php echo esc_attr($contact_email); ?>">
                            <?php esc_html_e('Email the Church', 'church-theme'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="visit-note__meta">
                <?php if ($service_times !== []) : ?>
                    <p><?php echo esc_html($service_times[0]); ?></p>
                <?php endif; ?>

                <?php if ($worship_location !== []) : ?>
                    <p><?php echo esc_html($worship_location_name); ?></p>
                <?php endif; ?>
            </div>
        </article>

        <div class="contact-grid">
            <article class="card contact-panel contact-panel--details reveal">
                <div class="contact-panel__header">
                    <p class="eyebrow"><?php esc_html_e('Visit and Connect', 'church-theme'); ?></p>
                    <h2><?php esc_html_e('Ways to reach us before Sunday.', 'church-theme'); ?></h2>
                    <p class="contact-panel__intro"><?php esc_html_e('Use the details below for direct contact, weekly gathering times, and the worship location.', 'church-theme'); ?></p>
                </div>

                <dl class="detail-list detail-list--contact">
                    <?php if ($contact_email !== '') : ?>
                        <div class="detail-list__item">
                            <dt><?php echo church_theme_icon('envelope', ['size' => 14]); ?><?php esc_html_e('Email', 'church-theme'); ?></dt>
                            <dd><a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($contact_phone !== '') : ?>
                        <div class="detail-list__item">
                            <dt><?php echo church_theme_icon('phone', ['size' => 14]); ?><?php esc_html_e('Phone', 'church-theme'); ?></dt>
                            <dd><a href="tel:<?php echo esc_attr(church_theme_phone_href($contact_phone)); ?>"><?php echo esc_html($contact_phone); ?></a></dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($service_times !== []) : ?>
                        <div class="detail-list__item">
                            <dt><?php echo church_theme_icon('clock', ['size' => 14]); ?><?php esc_html_e('Service Times', 'church-theme'); ?></dt>
                            <dd><?php echo esc_html(implode(' | ', $service_times)); ?></dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($worship_location !== []) : ?>
                        <div class="detail-list__item">
                            <dt><?php echo church_theme_icon('location', ['size' => 14]); ?><?php esc_html_e('Worship Location', 'church-theme'); ?></dt>
                            <dd><?php echo esc_html(implode(', ', $worship_location)); ?></dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($communication_address !== []) : ?>
                        <div class="detail-list__item">
                            <dt><?php echo church_theme_icon('address', ['size' => 14]); ?><?php esc_html_e('Communication Address', 'church-theme'); ?></dt>
                            <dd><?php echo esc_html(implode(', ', $communication_address)); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </article>

            <article class="card contact-panel contact-panel--form reveal">
                <div class="contact-panel__header">
                    <p class="eyebrow"><?php esc_html_e('Visitor Contact', 'church-theme'); ?></p>
                    <h2><?php echo esc_html(church_theme_get_mod('contact_form_heading')); ?></h2>
                    <p class="contact-panel__intro"><?php esc_html_e('Share your name, email, and message, and add a phone number if you would like a call back.', 'church-theme'); ?></p>
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
    </div>
</section>

<?php if ($map_embed_url !== '' || $map_directions_url !== '') : ?>
    <section class="section section--muted">
        <div class="wrap contact-map-section">
            <div class="section-heading contact-map-section__heading reveal">
                <p class="eyebrow"><?php esc_html_e('Location', 'church-theme'); ?></p>
                <h2><?php esc_html_e('Find the worship hall.', 'church-theme'); ?></h2>
                <p class="page-hero__summary"><?php printf(esc_html__('We gather at %s each Sunday. Use the map below if you need help planning your route.', 'church-theme'), esc_html($worship_location_name)); ?></p>

                <?php if ($map_directions_url !== '') : ?>
                    <div class="contact-map-section__actions">
                        <a class="button button--secondary" href="<?php echo esc_url($map_directions_url); ?>" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e('Open in Google Maps', 'church-theme'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="map-frame reveal">
                <?php if ($map_embed_url !== '') : ?>
                    <?php if ($map_directions_url !== '') : ?>
                        <div class="map-frame__fallback">
                            <p><?php esc_html_e('If the embedded map does not appear, open the church location directly in Google Maps.', 'church-theme'); ?></p>
                        </div>
                    <?php endif; ?>

                    <iframe
                        src="<?php echo esc_url($map_embed_url); ?>"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen
                        title="<?php esc_attr_e('Church location map', 'church-theme'); ?>"></iframe>
                <?php else : ?>
                    <div class="map-frame__placeholder">
                        <p><?php esc_html_e('The map embed is unavailable right now. Use the Google Maps link above for directions to the worship hall.', 'church-theme'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
<?php
get_footer();
