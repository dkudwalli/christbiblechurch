<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

the_post();

$sections = church_theme_get_child_sections(get_the_ID());
$service_times = church_theme_split_lines(church_theme_get_mod('service_times'));
$worship_location = church_theme_split_lines(church_theme_get_mod('worship_location'));
?>
<section class="page-hero">
    <div class="wrap page-hero__grid">
        <div>
            <h1><?php the_title(); ?></h1>
            <div class="page-hero__summary prose prose--compact">
                <?php echo apply_filters('the_content', get_the_content()); ?>
            </div>
        </div>

        <div class="card page-hero__panel">
            <p class="card__label"><?php esc_html_e('Gather With Us', 'church-theme'); ?></p>
            <?php if ($service_times !== []) : ?>
                <ul class="stack-list">
                    <?php foreach ($service_times as $service_time) : ?>
                        <li><?php echo esc_html($service_time); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($worship_location !== []) : ?>
                <div class="page-hero__panel-meta">
                    <strong><?php esc_html_e('Location', 'church-theme'); ?></strong>
                    <p><?php echo esc_html(implode(', ', $worship_location)); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
get_template_part('template-parts/page', 'sections-body', [
    'sections' => $sections,
    'nav_label' => __('Worship page sections', 'church-theme'),
    'cta' => [
        'eyebrow' => __('Questions', 'church-theme'),
        'heading' => __('Need help before your first Sunday at Crossroads?', 'church-theme'),
        'body' => __('Send a message if you want directions, more detail on age-group ministries, or help connecting with the church.', 'church-theme'),
        'primary_label' => __('Contact Us', 'church-theme'),
        'primary_url' => church_theme_get_page_url('contact-us'),
        'secondary_label' => __('About Crossroads', 'church-theme'),
        'secondary_url' => church_theme_get_page_url('about-us'),
    ],
]);
?>
<?php
get_footer();
