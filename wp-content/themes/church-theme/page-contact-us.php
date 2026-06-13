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
$worship_location_name = $worship_location[0] ?? __('our worship hall', 'church-theme');
$has_direct_actions = $contact_phone !== '' || $contact_email !== '';
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
                            <dt><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -2px; margin-right: 4px; opacity: 0.8;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg><?php esc_html_e('Email', 'church-theme'); ?></dt>
                            <dd><a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($contact_phone !== '') : ?>
                        <div class="detail-list__item">
                            <dt><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -2px; margin-right: 4px; opacity: 0.8;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg><?php esc_html_e('Phone', 'church-theme'); ?></dt>
                            <dd><a href="tel:<?php echo esc_attr(church_theme_phone_href($contact_phone)); ?>"><?php echo esc_html($contact_phone); ?></a></dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($service_times !== []) : ?>
                        <div class="detail-list__item">
                            <dt><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -2px; margin-right: 4px; opacity: 0.8;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg><?php esc_html_e('Service Times', 'church-theme'); ?></dt>
                            <dd><?php echo esc_html(implode(' | ', $service_times)); ?></dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($worship_location !== []) : ?>
                        <div class="detail-list__item">
                            <dt><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -2px; margin-right: 4px; opacity: 0.8;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg><?php esc_html_e('Worship Location', 'church-theme'); ?></dt>
                            <dd><?php echo esc_html(implode(', ', $worship_location)); ?></dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($communication_address !== []) : ?>
                        <div class="detail-list__item">
                            <dt><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -2px; margin-right: 4px; opacity: 0.8;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg><?php esc_html_e('Communication Address', 'church-theme'); ?></dt>
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

<?php if ($map_embed_url !== '') : ?>
    <section class="section section--muted">
        <div class="wrap contact-map-section">
            <div class="section-heading contact-map-section__heading reveal">
                <p class="eyebrow"><?php esc_html_e('Location', 'church-theme'); ?></p>
                <h2><?php esc_html_e('Find the worship hall.', 'church-theme'); ?></h2>
                <p class="page-hero__summary"><?php printf(esc_html__('We gather at %s each Sunday. Use the map below if you need help planning your route.', 'church-theme'), esc_html($worship_location_name)); ?></p>
            </div>

            <div class="map-frame reveal">
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
