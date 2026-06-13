<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

the_post();

$sections = church_theme_get_child_sections(get_the_ID());
?>
<section class="page-hero">
    <div class="wrap">
        <h1><?php the_title(); ?></h1>
        <div class="page-hero__summary prose prose--compact">
            <?php echo apply_filters('the_content', get_the_content()); ?>
        </div>
    </div>
</section>

<?php
get_template_part('template-parts/page', 'sections-body', [
    'sections' => $sections,
    'nav_label' => __('About page sections', 'church-theme'),
    'cta' => [
        'eyebrow' => __('Visit', 'church-theme'),
        'heading' => __('Meet the Crossroads church family in person.', 'church-theme'),
        'body' => __('Reach out before Sunday if you want directions, details about children’s ministry, or help planning your first visit.', 'church-theme'),
        'primary_label' => __('Contact Us', 'church-theme'),
        'primary_url' => church_theme_get_page_url('contact-us'),
        'secondary_label' => __('Learn About Worship', 'church-theme'),
        'secondary_url' => church_theme_get_page_url('worship'),
    ],
]);
?>
<?php
get_footer();
