<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

$contact_phone = church_theme_get_mod('contact_phone');
$contact_email = church_theme_get_mod('contact_email');
$contact_address = church_theme_split_lines(church_theme_get_mod('contact_address'));
$map_embed_url = church_theme_get_mod('map_embed_url');
?>
<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow"><?php esc_html_e('Connect', 'church-theme'); ?></p>
        <h1><?php the_title(); ?></h1>
    </div>
</section>

<section class="section">
    <div class="wrap contact-page">
        <article class="card card--accent visit-note">
            <div>
                <p class="card__label"><?php esc_html_e('Plan Your Visit', 'church-theme'); ?></p>
                <h2><?php esc_html_e('A smooth first visit starts with a quick message.', 'church-theme'); ?></h2>
                <p><?php esc_html_e('We are a church family that currently gathers in a home environment. We love this setting because it allows for meaningful fellowship, personal care, and focused study of God’s Word.', 'church-theme'); ?></p>
                <p><?php esc_html_e('Because we meet inside a gated community, we want to make sure your arrival is as smooth as possible. Neighborhood security restricts entry, so all guests need prior approval and a specific access code to come inside.', 'church-theme'); ?></p>
                <p><?php esc_html_e('If you would like to join us, please contact us in advance. For the quickest response, give us a call or send a message to the mobile number provided below. We would love to meet you and will happily provide you with the access code and directions.', 'church-theme'); ?></p>
            </div>

            <?php if ($contact_phone !== '') : ?>
                <div class="visit-note__actions">
                    <a class="button button--secondary" href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $contact_phone)); ?>">
                        <?php echo esc_html($contact_phone); ?>
                    </a>
                </div>
            <?php endif; ?>
        </article>

        <div class="contact-grid">
        <div class="card">
            <h2><?php esc_html_e('Contact Details', 'church-theme'); ?></h2>

            <?php if ($contact_phone !== '') : ?>
                <p><strong><?php esc_html_e('Phone:', 'church-theme'); ?></strong> <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $contact_phone)); ?>"><?php echo esc_html($contact_phone); ?></a></p>
            <?php endif; ?>

            <?php if ($contact_email !== '') : ?>
                <p><strong><?php esc_html_e('Email:', 'church-theme'); ?></strong> <a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></p>
            <?php endif; ?>

            <?php if ($contact_address !== []) : ?>
                <p><strong><?php esc_html_e('Address:', 'church-theme'); ?></strong><br><?php echo esc_html(implode(', ', $contact_address)); ?></p>
            <?php endif; ?>

            <div class="prose prose--compact">
                <?php while (have_posts()) : the_post(); ?>
                    <?php the_content(); ?>
                <?php endwhile; ?>
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
