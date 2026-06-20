<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

the_post();

$sections = church_theme_get_child_sections(get_the_ID());
?>
<?php
get_template_part('template-parts/page-hero', null, [
    'title' => get_the_title(),
    'content_html' => apply_filters('the_content', get_the_content()),
]);
?>

<?php
get_template_part('template-parts/page', 'sections-body', [
    'sections' => $sections,
    'nav_label' => __('About page sections', 'church-theme'),
    'cta' => [
        'eyebrow' => __('Visit', 'church-theme'),
        'heading' => __('Meet the Crossroad South Church family in person.', 'church-theme'),
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
