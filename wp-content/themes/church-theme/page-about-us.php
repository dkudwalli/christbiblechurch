<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

the_post();

$page_slug = (string) get_post_field('post_name', get_the_ID());
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

<?php if ($sections !== []) : ?>
    <section class="section section--muted section-nav-band">
        <div class="wrap">
            <?php
            get_template_part('template-parts/section', 'nav', [
                'sections' => $sections,
                'label' => __('About page sections', 'church-theme'),
            ]);
            ?>
        </div>
    </section>

    <?php foreach ($sections as $index => $section) : ?>
        <?php
        get_template_part('template-parts/page', 'section', [
            'section' => $section,
            'page_slug' => $page_slug,
            'index' => $index,
        ]);
        ?>
    <?php endforeach; ?>
<?php endif; ?>

<section class="section">
    <div class="wrap callout">
        <div>
            <p class="eyebrow"><?php esc_html_e('Visit', 'church-theme'); ?></p>
            <h2><?php esc_html_e('Meet the Crossroads church family in person.', 'church-theme'); ?></h2>
            <p><?php esc_html_e('Reach out before Sunday if you want directions, details about children’s ministry, or help planning your first visit.', 'church-theme'); ?></p>
        </div>

        <div class="callout__actions">
            <a class="button" href="<?php echo esc_url(church_theme_get_page_url('contact-us')); ?>">
                <?php esc_html_e('Contact Us', 'church-theme'); ?>
            </a>
            <a class="text-link" href="<?php echo esc_url(church_theme_get_page_url('worship')); ?>">
                <?php esc_html_e('Learn About Worship', 'church-theme'); ?>
            </a>
        </div>
    </div>
</section>
<?php
get_footer();
