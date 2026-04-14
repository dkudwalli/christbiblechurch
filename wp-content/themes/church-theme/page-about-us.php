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
        <p class="eyebrow"><?php esc_html_e('About Us', 'church-theme'); ?></p>
        <h1><?php the_title(); ?></h1>
        <div class="page-hero__summary prose prose--compact">
            <?php echo apply_filters('the_content', get_the_content()); ?>
        </div>
    </div>
</section>

<?php if ($sections !== []) : ?>
    <section class="section section--muted section-nav-band">
        <div class="wrap">
            <nav class="section-nav" aria-label="<?php esc_attr_e('About page sections', 'church-theme'); ?>">
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
        $elder_board = $section_slug === 'elder-board' ? church_theme_get_elder_board_cards($section, $section_media) : [];
        $has_elder_cards = ($elder_board['cards'] ?? []) !== [];
        ?>
        <section id="<?php echo esc_attr(church_theme_get_section_anchor($section)); ?>" class="section<?php echo $index % 2 === 1 ? ' section--muted' : ''; ?>">
            <div class="wrap section-layout">
                <div class="section-heading">
                    <p class="eyebrow"><?php esc_html_e('About Us', 'church-theme'); ?></p>
                    <h2><?php echo esc_html(get_the_title($section)); ?></h2>
                </div>

                <?php if ($has_elder_cards) : ?>
                    <?php if (($elder_board['intro'] ?? '') !== '') : ?>
                        <div class="elder-board-intro prose prose--wide">
                            <?php echo wp_kses_post((string) $elder_board['intro']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="elder-board-grid">
                        <?php foreach ($elder_board['cards'] as $card) : ?>
                            <?php $image = $card['image']; ?>
                            <article class="card elder-card">
                                <div class="elder-card__media-frame">
                                    <img
                                        class="elder-card__media"
                                        src="<?php echo esc_url((string) $image['src']); ?>"
                                        alt="<?php echo esc_attr((string) $image['alt']); ?>"
                                        <?php if (! empty($image['width'])) : ?>width="<?php echo esc_attr((string) $image['width']); ?>"<?php endif; ?>
                                        <?php if (! empty($image['height'])) : ?>height="<?php echo esc_attr((string) $image['height']); ?>"<?php endif; ?>
                                        <?php if (! empty($image['object_position'])) : ?>style="object-position: <?php echo esc_attr((string) $image['object_position']); ?>;"<?php endif; ?>
                                        loading="lazy">
                                </div>

                                <div class="elder-card__body">
                                    <?php if (($card['family'] ?? '') !== '') : ?>
                                        <p class="elder-card__family"><?php echo esc_html((string) $card['family']); ?></p>
                                    <?php endif; ?>

                                    <h3><?php echo esc_html((string) $card['name']); ?></h3>

                                    <div class="prose prose--compact elder-card__content">
                                        <?php echo wp_kses_post((string) $card['content']); ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <?php if (($section_media['layout'] ?? '') === 'gallery') : ?>
                        <div class="section-media-grid">
                            <?php foreach ($section_media['items'] as $item) : ?>
                                <figure class="card person-card">
                                    <img
                                        class="person-card__media"
                                        src="<?php echo esc_url((string) $item['src']); ?>"
                                        alt="<?php echo esc_attr((string) $item['alt']); ?>"
                                        width="<?php echo esc_attr((string) $item['width']); ?>"
                                        height="<?php echo esc_attr((string) $item['height']); ?>"
                                        loading="lazy">
                                    <figcaption class="person-card__caption"><?php echo esc_html((string) $item['caption']); ?></figcaption>
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

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
