<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

the_post();

$page_slug = (string) get_post_field('post_name', get_the_ID());
$sections = church_theme_get_child_sections(get_the_ID());
$service_times = church_theme_split_lines(church_theme_get_mod('service_times'));
$worship_location = church_theme_split_lines(church_theme_get_mod('worship_location'));
?>
<section class="page-hero">
    <div class="wrap page-hero__grid">
        <div>
            <p class="eyebrow"><?php esc_html_e('Worship', 'church-theme'); ?></p>
            <h1><?php the_title(); ?></h1>
            <div class="page-hero__summary prose prose--compact">
                <?php echo apply_filters('the_content', get_the_content()); ?>
            </div>
        </div>

        <aside class="card page-hero__panel">
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
        </aside>
    </div>
</section>

<?php if ($sections !== []) : ?>
    <section class="section section--muted section-nav-band">
        <div class="wrap">
            <nav class="section-nav" aria-label="<?php esc_attr_e('Worship page sections', 'church-theme'); ?>">
                <ul class="section-nav__list">
                    <?php foreach ($sections as $section) : ?>
                        <li><a href="#<?php echo esc_attr(church_theme_get_section_anchor($section)); ?>"><?php echo esc_html(get_the_title($section)); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </section>

    <?php foreach ($sections as $index => $section) : ?>
        <?php
        $section_slug = church_theme_get_section_anchor($section);
        $section_media = church_theme_get_section_media($page_slug, $section_slug);
        ?>
        <section id="<?php echo esc_attr(church_theme_get_section_anchor($section)); ?>" class="section<?php echo $index % 2 === 1 ? ' section--muted' : ''; ?>">
            <div class="wrap section-layout">
                <div class="section-heading">
                    <p class="eyebrow"><?php esc_html_e('Worship', 'church-theme'); ?></p>
                    <h2><?php echo esc_html(get_the_title($section)); ?></h2>
                </div>

                <?php if (($section_media['layout'] ?? '') === 'feature') : ?>
                    <div class="section-story">
                        <figure class="card section-visual">
                            <img
                                src="<?php echo esc_url((string) $section_media['item']['src']); ?>"
                                alt="<?php echo esc_attr((string) $section_media['item']['alt']); ?>"
                                width="<?php echo esc_attr((string) $section_media['item']['width']); ?>"
                                height="<?php echo esc_attr((string) $section_media['item']['height']); ?>"
                                loading="lazy">
                        </figure>

                        <article class="card section-card prose prose--wide">
                            <?php echo apply_filters('the_content', $section->post_content); ?>
                        </article>
                    </div>
                <?php else : ?>
                    <article class="card section-card prose prose--wide">
                        <?php echo apply_filters('the_content', $section->post_content); ?>
                    </article>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

<section class="section">
    <div class="wrap callout">
        <div>
            <p class="eyebrow"><?php esc_html_e('Questions', 'church-theme'); ?></p>
            <h2><?php esc_html_e('Need help before your first Sunday at Crossroads?', 'church-theme'); ?></h2>
            <p><?php esc_html_e('Send a message if you want directions, more detail on age-group ministries, or help connecting with the church.', 'church-theme'); ?></p>
        </div>

        <div class="callout__actions">
            <a class="button" href="<?php echo esc_url(church_theme_get_page_url('contact-us')); ?>">
                <?php esc_html_e('Contact Us', 'church-theme'); ?>
            </a>
            <a class="text-link" href="<?php echo esc_url(church_theme_get_page_url('about-us')); ?>">
                <?php esc_html_e('About Crossroads', 'church-theme'); ?>
            </a>
        </div>
    </div>
</section>
<?php
get_footer();
