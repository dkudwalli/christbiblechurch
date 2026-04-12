<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

the_post();

$about_summary = church_theme_get_mod('about_summary');
$doctrines = church_theme_get_about_doctrines();
$leadership = church_theme_get_about_leadership();
?>
<section class="page-hero about-hero">
    <div class="wrap">
        <div class="about-hero__content">
            <p class="eyebrow"><?php esc_html_e('Who We Are', 'church-theme'); ?></p>
            <h1><?php the_title(); ?></h1>

            <?php if ($about_summary !== '') : ?>
                <p class="page-hero__summary"><?php echo esc_html($about_summary); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap about-section">
        <div class="section-heading about-section__intro">
            <p class="eyebrow"><?php esc_html_e('What We Teach', 'church-theme'); ?></p>
            <h2><?php esc_html_e('The truth we gladly confess and proclaim.', 'church-theme'); ?></h2>
            <p><?php esc_html_e('Our doctrine is shaped by the authority of Scripture and centered on the Lord Jesus Christ.', 'church-theme'); ?></p>
        </div>

        <div class="about-doctrine-grid">
            <?php foreach ($doctrines as $doctrine) : ?>
                <article class="card about-doctrine">
                    <h3><?php echo esc_html($doctrine['title']); ?></h3>
                    <p><?php echo esc_html($doctrine['summary']); ?></p>
                    <p class="about-doctrine__references"><?php echo esc_html($doctrine['references']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--muted">
    <div class="wrap about-leadership">
        <div class="section-heading">
            <p class="eyebrow"><?php esc_html_e('Our Leadership', 'church-theme'); ?></p>
            <h2><?php esc_html_e('Serving the church through teaching, shepherding, and care.', 'church-theme'); ?></h2>
        </div>

        <div class="about-leadership__grid">
            <aside class="card card--accent about-leadership__profile">
                <p class="card__label"><?php echo esc_html($leadership['role']); ?></p>
                <h3><?php echo esc_html($leadership['name']); ?></h3>
                <p class="about-leadership__highlight"><?php echo esc_html($leadership['highlight']); ?></p>
                <p class="about-leadership__family"><?php echo esc_html($leadership['family']); ?></p>
            </aside>

            <article class="card about-leadership__story prose prose--compact">
                <?php foreach ($leadership['paragraphs'] as $paragraph) : ?>
                    <p><?php echo esc_html($paragraph); ?></p>
                <?php endforeach; ?>
            </article>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap callout">
        <div>
            <p class="eyebrow"><?php esc_html_e('Visit', 'church-theme'); ?></p>
            <h2><?php esc_html_e('Plan your first visit with us.', 'church-theme'); ?></h2>
            <p><?php esc_html_e('If you are considering joining us on a Sunday, reach out ahead of time and we will help you with directions, access, and any questions you may have.', 'church-theme'); ?></p>
        </div>

        <a class="button" href="<?php echo esc_url(church_theme_get_page_url('contact')); ?>">
            <?php esc_html_e('Contact Us', 'church-theme'); ?>
        </a>
    </div>
</section>
<?php
get_footer();
