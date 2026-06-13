<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

the_post();

$instagram_profile_url = church_theme_get_instagram_profile_url();
$instagram_feed = church_theme_get_instagram_feed();
$gallery_feature = church_theme_get_gallery_feature_media();
?>
<section class="page-hero">
    <div class="wrap<?php echo $gallery_feature ? ' page-hero__grid' : ''; ?>">
        <div>
            <h1><?php the_title(); ?></h1>
            <div class="page-hero__summary prose prose--compact">
                <?php echo apply_filters('the_content', get_the_content()); ?>
            </div>
        </div>

        <?php if ($gallery_feature) : ?>
            <figure class="card page-hero__media">
                <?php
                echo church_theme_render_static_image($gallery_feature, [
                    'loading' => 'eager',
                    'fetchpriority' => 'high',
                    'sizes' => '(max-width: 960px) 100vw, 42vw',
                ]);
                ?>
            </figure>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="wrap content-grid">
        <?php if ($instagram_feed['items'] !== []) : ?>
            <article class="card gallery-feed reveal">
                <div class="gallery-feed__header">
                    <div>
                        <p class="eyebrow"><?php esc_html_e('Instagram', 'church-theme'); ?></p>
                        <h2><?php esc_html_e('Recent church life', 'church-theme'); ?></h2>
                    </div>

                    <?php if ($instagram_profile_url !== '') : ?>
                        <a class="text-link" href="<?php echo esc_url($instagram_profile_url); ?>" target="_blank" rel="noreferrer noopener">
                            <?php esc_html_e('Follow on Instagram', 'church-theme'); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="gallery-feed__grid">
                    <?php foreach ($instagram_feed['items'] as $item) : ?>
                        <?php
                        $timestamp = strtotime((string) ($item['timestamp'] ?? ''));
                        $caption = trim((string) ($item['caption'] ?? ''));
                        ?>
                        <article class="gallery-card">
                            <a class="gallery-card__media js-lightbox" href="<?php echo esc_url((string) $item['image_url']); ?>" data-permalink="<?php echo esc_url((string) $item['permalink']); ?>" aria-label="<?php esc_attr_e('View image in lightbox', 'church-theme'); ?>">
                                <img src="<?php echo esc_url((string) $item['image_url']); ?>" alt="<?php echo esc_attr($caption !== '' ? $caption : __('Crossroad South Church Instagram post', 'church-theme')); ?>" loading="lazy" decoding="async">
                            </a>

                            <div class="gallery-card__meta">
                                <p class="gallery-card__type"><?php echo esc_html(ucwords(strtolower(str_replace('_', ' ', (string) ($item['media_type'] ?? 'Photo'))))); ?></p>

                                <?php if ($caption !== '') : ?>
                                    <p><?php echo esc_html(wp_trim_words($caption, 20)); ?></p>
                                <?php endif; ?>

                                <div class="gallery-card__actions">
                                    <?php if ($timestamp) : ?>
                                        <span><?php echo esc_html(wp_date('M j, Y', $timestamp)); ?></span>
                                    <?php endif; ?>

                                    <a class="text-link" href="<?php echo esc_url((string) $item['permalink']); ?>" target="_blank" rel="noreferrer noopener">
                                        <?php esc_html_e('View Post', 'church-theme'); ?>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php else : ?>
            <article class="card gallery-feed reveal">
                <div class="gallery-feed__header">
                    <div>
                        <p class="eyebrow"><?php esc_html_e('Church Life', 'church-theme'); ?></p>
                        <h2><?php esc_html_e('A glimpse of our community.', 'church-theme'); ?></h2>
                    </div>

                    <?php if ($instagram_profile_url !== '') : ?>
                        <a class="text-link" href="<?php echo esc_url($instagram_profile_url); ?>" target="_blank" rel="noreferrer noopener">
                            <?php esc_html_e('Follow on Instagram', 'church-theme'); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="gallery-feed__grid">
                    <?php
                    $fallback_images = [
                        ['/assets/images/crossroads/kishore-shirley.webp', __('Kishore and Shirley', 'church-theme')],
                        ['/assets/images/crossroads/benji-rashmi.webp', __('Benji and Rashmi', 'church-theme')],
                        ['/assets/images/crossroads/tim-ruth.webp', __('Tim and Ruth', 'church-theme')],
                        ['/assets/images/crossroads/women-ministry.webp', __('Women\'s Ministry', 'church-theme')],
                    ];
                    foreach ($fallback_images as [$img_path, $img_alt]) :
                        $img_asset = church_theme_get_static_image($img_path, $img_alt);
                        if ($img_asset === null) {
                            continue;
                        }
                    ?>
                        <article class="gallery-card">
                            <div class="gallery-card__media">
                                <?php
                                echo church_theme_render_static_image($img_asset, [ // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    'loading' => 'lazy',
                                    'decoding' => 'async',
                                    'sizes' => '(max-width: 720px) 50vw, 25vw',
                                ]);
                                ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endif; ?>

        <article class="card content-placeholder gallery-state reveal">
            <p class="eyebrow"><?php esc_html_e('Teaching', 'church-theme'); ?></p>
            <h2><?php esc_html_e('Browse recent preaching while the gallery grows.', 'church-theme'); ?></h2>
            <p><?php esc_html_e('Catch up on the latest sermons or get in touch before your first Sunday at Crossroad South Church.', 'church-theme'); ?></p>
            <div class="callout__actions">
                <a class="button" href="<?php echo esc_url(church_theme_get_sermon_archive_url()); ?>">
                    <?php esc_html_e('View Sermons', 'church-theme'); ?>
                </a>
                <a class="text-link" href="<?php echo esc_url(church_theme_get_page_url('worship')); ?>">
                    <?php esc_html_e('Explore Worship', 'church-theme'); ?>
                </a>
            </div>
        </article>
    </div>
</section>
<?php
get_footer();
