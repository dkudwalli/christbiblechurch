<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    $post_id = get_the_ID();
    $album_date = church_theme_get_photo_album_date($post_id);
    $album_content = get_the_content();
    $has_album_content = trim(wp_strip_all_tags($album_content)) !== '';
    $album_photos = church_theme_get_photo_album_photo_assets($post_id);

    $breadcrumb_items = [
        ['label' => __('Home', 'church-theme'), 'url' => home_url('/')],
        ['label' => __('Gallery', 'church-theme'), 'url' => church_theme_get_photo_album_archive_url()],
        ['label' => wp_trim_words(get_the_title(), 5)],
    ];
    ?>
    <?php
    get_template_part('template-parts/page-hero', null, [
        'title' => get_the_title(),
        'breadcrumbs' => $breadcrumb_items,
        'summary' => $album_date,
    ]);
    ?>

    <section class="section">
        <div class="wrap album-detail">
            <?php if ($has_album_content) : ?>
                <article class="card album-detail__summary reveal">
                    <p class="eyebrow"><?php esc_html_e('Album Notes', 'church-theme'); ?></p>
                    <div class="prose prose--wide">
                        <?php echo apply_filters('the_content', $album_content); ?>
                    </div>
                </article>
            <?php endif; ?>

            <article class="card album-detail__photos reveal">
                <div class="gallery-feed__header">
                    <div>
                        <p class="eyebrow"><?php esc_html_e('Photos', 'church-theme'); ?></p>
                        <h2><?php esc_html_e('Moments from this gathering.', 'church-theme'); ?></h2>
                    </div>
                </div>

                <?php if ($album_photos !== []) : ?>
                    <div class="album-photo-grid">
                        <?php foreach ($album_photos as $photo) : ?>
                            <?php
                            $caption = trim((string) ($photo['caption'] ?? ''));
                            $alt = trim((string) ($photo['alt'] ?? ''));
                            ?>
                            <article class="gallery-card">
                                <a
                                    class="gallery-card__media js-lightbox skeleton"
                                    href="<?php echo esc_url((string) $photo['src']); ?>"
                                    data-caption="<?php echo esc_attr($caption); ?>"
                                    data-lightbox-alt="<?php echo esc_attr($alt); ?>"
                                    aria-label="<?php esc_attr_e('View image in lightbox', 'church-theme'); ?>"
                                >
                                    <img
                                        src="<?php echo esc_url((string) $photo['src']); ?>"
                                        <?php if (! empty($photo['srcset'])) : ?>
                                        srcset="<?php echo esc_attr((string) $photo['srcset']); ?>"
                                        sizes="(max-width: 720px) 50vw, 25vw"
                                        <?php endif; ?>
                                        alt="<?php echo esc_attr($alt); ?>"
                                        loading="lazy"
                                        decoding="async"
                                        width="<?php echo (int) ($photo['width'] ?? 0); ?>"
                                        height="<?php echo (int) ($photo['height'] ?? 0); ?>">
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e('Photos for this album will be added soon.', 'church-theme'); ?></p>
                <?php endif; ?>
            </article>

            <article class="card album-detail__actions reveal">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Gallery', 'church-theme'); ?></p>
                    <h2><?php esc_html_e('Keep browsing church life.', 'church-theme'); ?></h2>
                    <p><?php esc_html_e('Return to the main gallery to explore other photo albums and recent updates.', 'church-theme'); ?></p>
                </div>

                <a class="button button--secondary" href="<?php echo esc_url(church_theme_get_photo_album_archive_url()); ?>">
                    <?php esc_html_e('Back to gallery', 'church-theme'); ?>
                </a>
            </article>
        </div>
    </section>
<?php endwhile; ?>
<?php
get_footer();
