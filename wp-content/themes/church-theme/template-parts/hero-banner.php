<?php
/**
 * Hero slideshow banner — renders above the main front-page hero when enabled
 * via the Customizer toggle.
 *
 * Mode selection:
 *   disabled / no media -> nothing renders (main hero stays at the top)
 *   video set           -> looping muted background video (images ignored)
 *   1 image             -> single static image (no controls)
 *   2+ images           -> crossfading slideshow with arrows + dots
 *
 * An optional dark overlay carries a heading, subtext, and CTA that animate in
 * on load. JavaScript (assets/js/site.js) drives the slideshow, intro, and
 * reduced-motion handling.
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! get_theme_mod('banner_enabled', false)) {
    return;
}

$banner_video = church_theme_get_banner_video();
$banner_images = $banner_video === null ? church_theme_get_banner_images() : [];

if ($banner_video === null && $banner_images === []) {
    return;
}

$is_slideshow = $banner_video === null && count($banner_images) > 1;

$banner_heading = church_theme_get_mod('banner_heading');
$banner_subtext = church_theme_split_lines(church_theme_get_mod('banner_subtext'));
$banner_cta_label = church_theme_get_mod('banner_cta_label');
$banner_cta_url = church_theme_resolve_url(church_theme_get_mod('banner_cta_url'));
$has_overlay_text = $banner_heading !== '' || $banner_subtext !== [] || ($banner_cta_label !== '' && $banner_cta_url !== '');

$section_classes = 'hero-banner';
if ($is_slideshow) {
    $section_classes .= ' hero-banner--slideshow';
}

$slide_count = count($banner_images);
?>
<section
    class="<?php echo esc_attr($section_classes); ?>"
    <?php if ($is_slideshow) : ?>
    data-hero-banner
    aria-roledescription="carousel"
    aria-label="<?php esc_attr_e('Featured announcements', 'church-theme'); ?>"
    aria-live="off"
    <?php endif; ?>
>
    <div class="hero-banner__media">
        <?php if ($banner_video !== null) : ?>
            <video
                class="hero-banner__video"
                autoplay
                muted
                loop
                playsinline
                preload="metadata"
                aria-hidden="true"
                <?php echo $banner_video['poster'] !== '' ? 'poster="' . esc_url($banner_video['poster']) . '"' : ''; ?>
            >
                <source src="<?php echo esc_url($banner_video['url']); ?>" type="<?php echo esc_attr($banner_video['mime']); ?>">
            </video>
        <?php else : ?>
            <?php foreach ($banner_images as $index => $image) : ?>
                <?php
                $is_first = $index === 0;
                $slide_attr = [
                    'class' => 'hero-banner__image',
                    'loading' => $is_first ? 'eager' : 'lazy',
                    'decoding' => $is_first ? 'sync' : 'async',
                    'fetchpriority' => $is_first ? 'high' : 'low',
                    'sizes' => '100vw',
                ];
                ?>
                <div
                    class="hero-banner__slide<?php echo $is_first ? ' is-active' : ''; ?>"
                    <?php if ($is_slideshow) : ?>
                    role="group"
                    aria-roledescription="<?php esc_attr_e('slide', 'church-theme'); ?>"
                    aria-label="<?php echo esc_attr(sprintf(/* translators: 1: current slide number, 2: total slides. */ __('Slide %1$d of %2$d', 'church-theme'), $index + 1, $slide_count)); ?>"
                        <?php echo $is_first ? '' : 'aria-hidden="true"'; ?>
                    <?php endif; ?>
                >
                    <?php echo wp_get_attachment_image($image['id'], 'full', false, $slide_attr); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="hero-banner__overlay" aria-hidden="true"></div>
    </div>

    <?php if ($has_overlay_text) : ?>
        <div class="wrap hero-banner__inner">
            <div class="hero-banner__content" data-hero-banner-intro>
                <?php if ($banner_heading !== '') : ?>
                    <h2 class="hero-banner__heading"><?php echo esc_html($banner_heading); ?></h2>
                <?php endif; ?>

                <?php foreach ($banner_subtext as $line) : ?>
                    <p class="hero-banner__subtext"><?php echo esc_html($line); ?></p>
                <?php endforeach; ?>

                <?php if ($banner_cta_label !== '' && $banner_cta_url !== '') : ?>
                    <a class="button hero-banner__cta" href="<?php echo esc_url($banner_cta_url); ?>">
                        <?php echo esc_html($banner_cta_label); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($is_slideshow) : ?>
        <button class="hero-banner__arrow hero-banner__arrow--prev" type="button" data-hero-prev>
            <?php echo church_theme_icon('arrow-left', ['size' => 24]); ?>
            <span class="screen-reader-text"><?php esc_html_e('Previous slide', 'church-theme'); ?></span>
        </button>
        <button class="hero-banner__arrow hero-banner__arrow--next" type="button" data-hero-next>
            <?php echo church_theme_icon('arrow-right', ['size' => 24]); ?>
            <span class="screen-reader-text"><?php esc_html_e('Next slide', 'church-theme'); ?></span>
        </button>

        <div class="hero-banner__dots" role="tablist" aria-label="<?php esc_attr_e('Choose slide', 'church-theme'); ?>">
            <?php foreach ($banner_images as $index => $image) : ?>
                <button
                    class="hero-banner__dot<?php echo $index === 0 ? ' is-active' : ''; ?>"
                    type="button"
                    role="tab"
                    data-hero-dot="<?php echo esc_attr((string) $index); ?>"
                    aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                >
                    <span class="screen-reader-text"><?php echo esc_html(sprintf(/* translators: %d: slide number. */ __('Go to slide %d', 'church-theme'), $index + 1)); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
